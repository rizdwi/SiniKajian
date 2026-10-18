<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Bookmark;
use App\Models\Kajian;
use Carbon\Carbon;
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
        Kajian::purgeExpiredKajians(3);

        $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);

        // Cache categories (berubah jarang)
        $categories = Cache::remember('kajian_categories', 3600, function () use ($cutoff) {
            return Kajian::where('verified', true)
                ->where(function ($q) use ($cutoff) {
                    $q->where('event_datetime', '>=', $cutoff)
                      ->orWhere('is_rutin', true);
                })
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();
        });

        // Query kajian terdekat & aktif (kajian belum lewat > 3 jam atau kajian rutin)
        $query = Kajian::where('verified', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('event_datetime', '>=', $cutoff)
                  ->orWhere('is_rutin', true);
            })
            ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'poster_url', 'event_datetime', 'source', 'source_url', 'is_rutin']);

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

        // JSON untuk peta & kartu feed client-side (hanya kajian aktif atau rutin)
        $mapKajians = Kajian::where('verified', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('event_datetime', '>=', $cutoff)
                  ->orWhere('is_rutin', true);
            })
            ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'poster_url', 'event_datetime', 'source', 'source_url', 'description', 'is_rutin'])
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
    public function apiList(): JsonResponse
    {
        Kajian::purgeExpiredKajians(3);
        $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);
        $kajians = Kajian::where('verified', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('event_datetime', '>=', $cutoff)
                  ->orWhere('is_rutin', true);
            })
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
        Kajian::purgeExpiredKajians(3);
        $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);
        $kajians = Kajian::where('verified', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('event_datetime', '>=', $cutoff)
                  ->orWhere('is_rutin', true);
            })
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
        Kajian::purgeExpiredKajians(3);
        $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);
        $user = Auth::user();
        $bookmarkedKajians = collect();
        $attendances = collect();

        if ($user) {
            $bookmarkedKajians = $this->getUserBookmarks($user->id)
                ->filter(function ($k) use ($cutoff) {
                    return $k->is_rutin || $k->event_datetime >= $cutoff;
                });
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
    public function toggleBookmark(Request $request, Kajian $kajian): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (!Auth::check()) {
            if (!$request->wantsJson() && !$request->ajax()) {
                return redirect()->route('login')->with('error', 'Silakan masuk untuk menyimpan bookmark.');
            }
            return response()->json([
                'status' => 'auth_required',
                'message' => 'Silakan masuk untuk menyimpan bookmark ke akun Anda.',
            ], 401);
        }

        $userId = Auth::id();
        $kajianId = $kajian->id;
        $saved = false;

        // 1. Gunakan Supabase untuk persistensi jika dikonfigurasi (SQLite Vercel ephemeral)
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey) {
            $headers = [
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
            ];

            try {
                // Cek existing di Supabase
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
            } catch (\Exception $e) {
                \Log::warning('Supabase bookmark gagal, fallback ke lokal: ' . $e->getMessage());
            }
        }

        // 2. Simpan juga di SQLite lokal (selalu tersinkron)
        $existingLocal = Bookmark::where('user_id', $userId)
            ->where('kajian_id', $kajianId)
            ->first();

        if ($existingLocal) {
            $existingLocal->delete();
            $saved = false;
        } else {
            Bookmark::create([
                'user_id' => $userId,
                'kajian_id' => $kajianId,
            ]);
            $saved = true;
        }

        $msg = $saved ? 'Kajian berhasil disimpan.' : 'Kajian dihapus dari tersimpan.';

        if (!$request->wantsJson() && !$request->ajax()) {
            return back()->with('success', $msg);
        }

        return response()->json([
            'status' => 'success',
            'saved' => $saved,
            'kajian_id' => $kajianId,
            'message' => $msg,
        ]);
    }

    /**
     * Sinkronkan bookmark dari localStorage klien ke database akun server.
     */
    public function syncBookmarks(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'unauthenticated', 'synced' => 0], 401);
        }

        $userId = Auth::id();
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['status' => 'empty', 'synced' => 0]);
        }

        $cleanIds = array_filter(array_map('intval', $ids), fn($v) => $v > 0);
        $validKajianIds = Kajian::whereIn('id', $cleanIds)->pluck('id')->toArray();
        $synced = 0;

        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');
        $hasSupabase = !empty($supabaseUrl) && !empty($supabaseKey);

        foreach ($validKajianIds as $kajianId) {
            $exists = Bookmark::where('user_id', $userId)
                ->where('kajian_id', $kajianId)
                ->exists();

            if (!$exists) {
                Bookmark::create([
                    'user_id' => $userId,
                    'kajian_id' => $kajianId,
                ]);
                $synced++;

                if ($hasSupabase) {
                    try {
                        \Illuminate\Support\Facades\Http::withHeaders([
                            'apikey' => $supabaseKey,
                            'Authorization' => 'Bearer ' . $supabaseKey,
                            'Prefer' => 'return=minimal',
                            'Content-Type' => 'application/json',
                        ])->timeout(5)->post("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                        ]);
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'synced' => $synced,
            'total' => Bookmark::where('user_id', $userId)->count(),
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

    /**
     * Ikuti atau berhenti mengikuti majelis taklim / masjid.
     */
    public function toggleFollowMajelis(Request $request): \Illuminate\Http\JsonResponse
    {
        $masjid = trim($request->input('masjid', ''));
        if (empty($masjid)) {
            return response()->json(['error' => 'Nama majelis/masjid diperlukan.'], 422);
        }

        $userId = Auth::id();
        $sessionToken = $request->session()->getId();
        $followed = false;

        // Cek tabel majelis_follows
        if (\Illuminate\Support\Facades\Schema::hasTable('majelis_follows')) {
            $query = \App\Models\MajelisFollow::where('masjid', $masjid);
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_token', $sessionToken);
            }
            $existing = $query->first();

            if ($existing) {
                $existing->delete();
                $followed = false;
            } else {
                \App\Models\MajelisFollow::create([
                    'user_id' => $userId,
                    'session_token' => $sessionToken,
                    'masjid' => $masjid,
                ]);
                $followed = true;
            }
        }

        // Simpan juga ke Session untuk Vercel serverless fallback
        $sessionFollows = session()->get('followed_majelis', []);
        if ($followed) {
            if (!in_array($masjid, $sessionFollows)) {
                $sessionFollows[] = $masjid;
            }
        } else {
            $sessionFollows = array_values(array_diff($sessionFollows, [$masjid]));
        }
        session()->put('followed_majelis', $sessionFollows);

        // Sinkronisasi Supabase jika aktif
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');
        if ($supabaseUrl && $supabaseKey && $userId) {
            $headers = [
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
            ];
            try {
                if ($followed) {
                    \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'resolution=ignore-duplicates', 'Content-Type' => 'application/json']))
                        ->timeout(6)
                        ->post("{$supabaseUrl}/rest/v1/majelis_follows", [
                            'user_id' => $userId,
                            'masjid' => $masjid,
                        ]);
                } else {
                    \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(6)
                        ->delete("{$supabaseUrl}/rest/v1/majelis_follows", [
                            'user_id' => "eq.{$userId}",
                            'masjid' => "eq.{$masjid}",
                        ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Supabase follow majelis gagal: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'followed' => $followed,
            'masjid' => $masjid,
        ]);
    }
}
