<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Bookmark;
use App\Models\Kajian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class KajianController extends Controller
{
    /**
     * Tampilkan halaman beranda jadwal kajian.
     */
    public function index(Request $request): View
    {
        // Otomatis hapus kajian yang sudah lewat 3-6 jam dari jadwal
        Kajian::purgeExpiredKajians(4);

        // Cache categories (berubah jarang)
        $categories = Cache::remember('kajian_categories', 3600, function () {
            return Kajian::where('verified', true)
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();
        });

        // Query kajian terdekat & aktif
        $query = Kajian::where('verified', true)
            ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'poster_url', 'event_datetime', 'source', 'source_url']);

        if ($request->filled('kategori') && $request->kategori !== 'Semua') {
            $query->where('category', $request->kategori);
        }

        if ($request->filled('hari') && $request->hari !== 'Semua') {
            $query->where('day', $request->hari);
        }

        if ($request->filled('audience') && $request->audience !== 'Semua') {
            $query->where('audience', $request->audience);
        }

        if ($request->filled('q')) {
            $like = '%' . $request->q . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('ustadz', 'like', $like)
                  ->orWhere('masjid', 'like', $like);
            });
        }

        // Urutkan berdasarkan waktu kajian terdekat
        $kajians = $query->orderBy('event_datetime', 'asc')->paginate(20)->withQueryString();

        // JSON untuk peta & kartu feed client-side
        $mapKajians = Kajian::where('verified', true)
            ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'poster_url', 'event_datetime', 'source', 'source_url', 'description'])
            ->orderBy('event_datetime', 'asc')
            ->get();
        $mapJson = $mapKajians->toJson();

        $userBookmarks = [];
        if (Auth::check()) {
            $userBookmarks = Bookmark::where('user_id', Auth::id())->pluck('kajian_id')->toArray();
        }

        return view('kajian.index', [
            'kajians' => $kajians,
            'allKajiansJson' => $mapJson,
            'categories' => $categories,
            'userBookmarks' => $userBookmarks,
        ]);
    }

    /**
     * Tampilkan halaman detail kajian (Screen 2).
     */
    public function show($id): View|\Illuminate\Http\RedirectResponse
    {
        $kajian = Kajian::find($id);
        if (!$kajian) {
            return redirect()->route('kajian.index')->with('error', 'Kajian tidak ditemukan atau telah berakhir.');
        }

        $isBookmarked = false;
        if (Auth::check()) {
            $isBookmarked = Bookmark::where('user_id', Auth::id())
                ->where('kajian_id', $kajian->id)
                ->exists();
        }

        return view('kajian.show', [
            'kajian' => $kajian,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    /**
     * API JSON untuk integrasi mobile atau map renderer.
     */
    public function apiList(Request $request): JsonResponse
    {
        Kajian::purgeExpiredKajians(4);
        $kajians = Kajian::where('verified', true)
            ->orderBy('event_datetime', 'asc')
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $kajians,
        ]);
    }

    /**
     * Tampilkan peta interaktif sebaran majelis ilmu.
     */
    public function map(): View
    {
        Kajian::purgeExpiredKajians(4);
        $kajians = Kajian::where('verified', true)
            ->orderBy('event_datetime', 'asc')
            ->get();

        return view('kajian.map', [
            'events' => $kajians,
            'kajians' => $kajians,
            'allKajiansJson' => $kajians->toJson(),
        ]);
    }

    /**
     * Tampilkan kompas arah kiblat Ka'bah.
     */
    public function qibla(): View
    {
        return view('kajian.qibla');
    }

    /**
     * Tampilkan daftar kajian tersimpan dan catatan kehadiran.
     */
    public function saved(): View
    {
        $user = Auth::user();
        $bookmarkedKajians = collect();
        $attendances = collect();

        if ($user) {
            $bookmarkedKajians = $this->getUserBookmarks($user->id);
            $attendances = $user->attendances()->with('kajian')->latest()->get();
        }

        return view('kajian.saved', [
            'savedKajians' => $bookmarkedKajians,
            'bookmarkedKajians' => $bookmarkedKajians,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Ambil bookmark user dari Supabase (persisten) dengan fallback lokal.
     */
    private function getUserBookmarks(int $userId)
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey) {
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                    ])
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/bookmarks", [
                        'user_id' => "eq.{$userId}",
                        'select' => 'kajian_id',
                    ]);
                $rows = $res->json();
                $ids = array_column($rows ?? [], 'kajian_id');
                if (!empty($ids)) {
                    return Kajian::whereIn('id', $ids)->get();
                }
                return collect();
            } catch (\Exception $e) {
                \Log::warning('Supabase get bookmarks gagal: ' . $e->getMessage());
            }
        }

        $user = \App\Models\User::find($userId);
        return $user ? $user->bookmarkedKajians()->get() : collect();
    }

    /**
     * Simpan pengajuan kajian baru untuk moderasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'ustadz' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'audience' => 'required|string|in:Umum,Ikhwan,Akhwat',
            'masjid' => 'required|string|max:255',
            'address' => 'required|string',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'day' => 'required|string',
            'time' => 'required|string|max:100',
            'description' => 'nullable|string',
            'source_url' => 'nullable|url',
        ]);

        $validated['verified'] = false;
        $validated['status'] = 'aktif';
        $validated['source'] = 'user_submission';
        $validated['user_id'] = Auth::id();

        Kajian::create($validated);

        return back()->with('success', 'Jadwal kajian berhasil diajukan dan sedang dalam antrean verifikasi tim moderasi.');
    }

    /**
     * Toggle bookmark kajian.
     */
    public function toggleBookmark(Request $request, Kajian $kajian): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'auth_required',
                'message' => 'Silakan masuk untuk menyimpan bookmark ke akun Anda.',
            ], 401);
        }

        $userId = Auth::id();
        $kajianId = $kajian->id;

        // Gunakan Supabase untuk persistensi (SQLite Vercel ephemeral)
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey) {
            $headers = [
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
            ];

            try {
                // Cek existing
                $res = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/bookmarks", [
                        'user_id' => "eq.{$userId}",
                        'kajian_id' => "eq.{$kajianId}",
                        'select' => 'id',
                    ]);
                $existing = $res->json();

                if (!empty($existing)) {
                    \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(10)
                        ->delete("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => "eq.{$userId}",
                            'kajian_id' => "eq.{$kajianId}",
                        ]);
                    $saved = false;
                } else {
                    \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'return=minimal', 'Content-Type' => 'application/json']))
                        ->timeout(10)
                        ->post("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                        ]);
                    $saved = true;
                }

                return response()->json([
                    'status' => 'success',
                    'saved' => $saved,
                    'kajian_id' => $kajianId,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Supabase bookmark gagal, fallback ke lokal: ' . $e->getMessage());
            }
        }

        // Fallback: SQLite lokal
        $existing = Bookmark::where('user_id', $userId)
            ->where('kajian_id', $kajianId)
            ->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            Bookmark::create([
                'user_id' => $userId,
                'kajian_id' => $kajianId,
            ]);
            $saved = true;
        }

        return response()->json([
            'status' => 'success',
            'saved' => $saved,
            'kajian_id' => $kajianId,
        ]);
    }

    /**
     * Catat kehadiran kajian (RSVP).
     */
    public function logAttendance(Request $request, Kajian $kajian): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'auth_required',
                'message' => 'Silakan masuk untuk menyimpan agenda ke akun Anda.',
            ], 401);
        }

        $userId = Auth::id();
        $kajianId = $kajian->id;
        $today = now()->toDateString();

        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        // Gunakan Supabase untuk persistensi
        if ($supabaseUrl && $supabaseKey) {
            $headers = [
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
            ];
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/attendances", [
                        'user_id' => "eq.{$userId}",
                        'kajian_id' => "eq.{$kajianId}",
                        'attendance_date' => "eq.{$today}",
                        'select' => 'id',
                    ]);

                if (!empty($res->json())) {
                    // Batalkan kehadiran
                    \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(10)
                        ->delete("{$supabaseUrl}/rest/v1/attendances", [
                            'user_id' => "eq.{$userId}",
                            'kajian_id' => "eq.{$kajianId}",
                            'attendance_date' => "eq.{$today}",
                        ]);
                    $attended = false;
                } else {
                    // Catat kehadiran
                    \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'return=minimal', 'Content-Type' => 'application/json']))
                        ->timeout(10)
                        ->post("{$supabaseUrl}/rest/v1/attendances", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                            'attendance_date' => $today,
                        ]);
                    // Otomatis bookmark juga
                    \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'resolution=ignore-duplicates', 'Content-Type' => 'application/json']))
                        ->timeout(10)
                        ->post("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                        ]);
                    $attended = true;
                }

                return response()->json([
                    'status' => 'success',
                    'attended' => $attended,
                    'kajian_id' => $kajianId,
                ]);
            } catch (\Exception $e) {
                \Log::warning('Supabase attendance gagal: ' . $e->getMessage());
            }
        }

        // Fallback SQLite
        $existing = Attendance::where('user_id', $userId)
            ->where('kajian_id', $kajianId)
            ->where('attendance_date', $today)
            ->first();

        if ($existing) {
            $existing->delete();
            $attended = false;
        } else {
            Attendance::create([
                'user_id' => $userId,
                'kajian_id' => $kajianId,
                'attendance_date' => $today,
            ]);
            Bookmark::firstOrCreate([
                'user_id' => $userId,
                'kajian_id' => $kajianId,
            ]);
            $attended = true;
        }

        return response()->json([
            'status' => 'success',
            'attended' => $attended,
            'kajian_id' => $kajianId,
        ]);
    }
    }
}
