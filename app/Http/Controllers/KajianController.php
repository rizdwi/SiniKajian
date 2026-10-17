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
        // Cache categories (berubah jarang)
        $categories = Cache::remember('kajian_categories', 3600, function () {
            return Kajian::where('verified', true)
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values();
        });

        // Single query dengan select kolom yang dibutuhkan saja
        $query = Kajian::where('verified', true)
            ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'flyer_url', 'event_date', 'source', 'source_url']);

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

        // Pagination 20 per halaman (bukan load semua)
        $kajians = $query->orderBy('event_date')->paginate(20)->withQueryString();

        // JSON untuk peta & kartu: cache 5 menit
        $mapJson = Cache::remember('kajian_map_json', 300, function () {
            return Kajian::where('verified', true)
                ->select(['id', 'title', 'ustadz', 'day', 'time', 'masjid', 'address', 'lat', 'lon', 'category', 'audience', 'poster_url', 'event_date', 'source', 'source_url', 'description'])
                ->get()
                ->toJson();
        });

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
        $kajians = Kajian::where('verified', true)->get();
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
        $kajians = Kajian::where('verified', true)->get();

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
            $bookmarkedKajians = $user->bookmarkedKajians()->get();
            $attendances = $user->attendances()->with('kajian')->latest()->get();
        }

        return view('kajian.saved', [
            'savedKajians' => $bookmarkedKajians,
            'bookmarkedKajians' => $bookmarkedKajians,
            'attendances' => $attendances,
        ]);
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
        if (Auth::check()) {
            $existing = Bookmark::where('user_id', Auth::id())
                ->where('kajian_id', $kajian->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $saved = false;
            } else {
                Bookmark::create([
                    'user_id' => Auth::id(),
                    'kajian_id' => $kajian->id,
                ]);
                $saved = true;
            }

            return response()->json([
                'status' => 'success',
                'saved' => $saved,
                'kajian_id' => $kajian->id,
            ]);
        }

        return response()->json([
            'status' => 'auth_required',
            'message' => 'Silakan masuk untuk menyimpan bookmark ke akun Anda.',
        ], 401);
    }

    /**
     * Catat kehadiran kajian (RSVP).
     */
    public function logAttendance(Request $request, Kajian $kajian): JsonResponse
    {
        if (Auth::check()) {
            $today = now()->toDateString();
            $existing = Attendance::where('user_id', Auth::id())
                ->where('kajian_id', $kajian->id)
                ->where('attendance_date', $today)
                ->first();

            if ($existing) {
                $existing->delete();
                $attended = false;
            } else {
                Attendance::create([
                    'user_id' => Auth::id(),
                    'kajian_id' => $kajian->id,
                    'attendance_date' => $today,
                ]);
                $attended = true;
            }

            $count = Attendance::where('user_id', Auth::id())->count();

            return response()->json([
                'status' => 'success',
                'attended' => $attended,
                'total_attendance' => $count,
                'kajian_id' => $kajian->id,
            ]);
        }

        return response()->json([
            'status' => 'auth_required',
            'message' => 'Silakan masuk untuk mencatat kehadiran kajian.',
        ], 401);
    }
}
