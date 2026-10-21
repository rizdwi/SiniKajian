<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Bookmark;
use App\Models\Kajian;
use App\Services\SupabaseKajianService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class KajianController extends Controller
{
    /**
     * Tampilkan halaman beranda jadwal kajian.
     * Data dibaca langsung dari Supabase (bukan SQLite yang ephemeral di Vercel).
     */
    public function index(Request $request): View
    {
        $service = new SupabaseKajianService();

        if ($service->isConfigured()) {
            return $this->indexFromSupabase($request, $service);
        }

        // Fallback ke SQLite jika Supabase belum dikonfigurasi
        return $this->indexFromSqlite($request);
    }

    /**
     * Index via Supabase REST API.
     */
    protected function indexFromSupabase(Request $request, SupabaseKajianService $service): View
    {
        $allRows = $service->getActiveKajians();
        $collection = SupabaseKajianService::toCollection($allRows);

        $categories = $service->getCategories();

        // JSON untuk peta (semua, tanpa filter/pagination) — dari hasil yang sama, tanpa API call ke-2
        $mapJson = $collection->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Filter
        if ($request->filled('kategori') && $request->kategori !== 'Semua') {
            $collection = $collection->where('category', $request->kategori);
        }
        if ($request->filled('hari') && $request->hari !== 'Semua') {
            $collection = $collection->where('day', $request->hari);
        }
        if ($request->filled('audience') && $request->audience !== 'Semua') {
            $collection = $collection->where('audience', $request->audience);
        }
        if ($request->filled('q')) {
            $q = strtolower($request->q);
            $collection = $collection->filter(function ($k) use ($q) {
                return str_contains(strtolower($k->title), $q)
                    || str_contains(strtolower($k->ustadz), $q)
                    || str_contains(strtolower($k->masjid), $q);
            });
        }

        // Sort: event_date terdekat dulu, null (rutin) di belakang
        $collection = $collection->sortBy([
            fn($a, $b) => ($a->event_date ?? '9999') <=> ($b->event_date ?? '9999'),
        ])->values();

        // Manual pagination
        $perPage = 20;
        $page = max(1, (int)$request->get('page', 1));
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();
        $kajians = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $userBookmarks = [];
        if (Auth::check()) {
            // Wajib dari Supabase: SQLite Vercel ephemeral selalu kosong/stale,
            // sehingga status bookmark di feed selalu salah saat halaman dimuat.
            $userBookmarks = $this->getUserBookmarkIds($this->appUserId() ?? 0);
        }

        return view('kajian.index', [
            'kajians' => $kajians,
            'allKajiansJson' => $mapJson,
            'categories' => $categories,
            'userBookmarks' => $userBookmarks,
            // Bedakan "gagal memuat" dari "memang kosong" (FE-H6)
            'kajianLoadError' => $service->lastFetchFailed && $collection->isEmpty(),
        ]);
    }

    /**
     * Index via SQLite (fallback).
     */
    protected function indexFromSqlite(Request $request): View
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
        $mapJson = $mapKajians->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

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
        $service = new SupabaseKajianService();

        if ($service->isConfigured()) {
            $row = $service->find((int)$id);
            if (!$row) {
                return redirect()->route('kajian.index')->with('error', 'Kajian tidak ditemukan atau telah berakhir.');
            }
            $kajian = SupabaseKajianService::toObject($row);
        } else {
            $kajian = Kajian::find($id);
            if (!$kajian) {
                return redirect()->route('kajian.index')->with('error', 'Kajian tidak ditemukan atau telah berakhir.');
            }
        }

        $isBookmarked = false;
        if (Auth::check()) {
            $isBookmarked = Bookmark::where('user_id', Auth::id())
                ->where('kajian_id', $kajian->id)
                ->exists();

            if (!$isBookmarked) {
                $supabaseUrl = config('services.supabase.url');
                $supabaseKey = config('services.supabase.service_key');
                if ($supabaseUrl && $supabaseKey) {
                    try {
                        $sbRes = \Illuminate\Support\Facades\Http::withHeaders([
                            'apikey' => $supabaseKey,
                            'Authorization' => 'Bearer ' . $supabaseKey,
                        ])->timeout(5)->get("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => 'eq.' . ($this->appUserId() ?? 0),
                            'kajian_id' => 'eq.' . $kajian->id,
                            'select' => 'id',
                        ]);
                        $isBookmarked = !empty($sbRes->json());
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }

        return view('kajian.show', [
            'kajian' => $kajian,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    /**
     * API JSON untuk integrasi mobile atau map renderer.
     * Data publik yang sama untuk semua pengunjung -> dilayani CDN Vercel
     * (s-maxage) supaya origin tidak menerima beban per pengunjung.
     * Respons kosong (indikasi kegagalan upstream) TIDAK ikut ter-cache.
     */
    public function apiList(): JsonResponse
    {
        $service = new SupabaseKajianService();

        if ($service->isConfigured()) {
            $rows = $service->getActiveKajians();
            $kajians = SupabaseKajianService::toCollection($rows);
        } else {
            Kajian::purgeExpiredKajians(3);
            $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);
            $kajians = Kajian::where('verified', true)
                ->where(function ($q) use ($cutoff) {
                    $q->where('event_datetime', '>=', $cutoff)
                      ->orWhere('is_rutin', true);
                })
                ->orderBy('event_datetime', 'asc')
                ->get();
        }

        // Pagination OPSIONAL (?per_page=20&page=2): tanpa parameter,
        // perilaku lama dipertahankan (semua data, dipakai aplikasi).
        $perPage = (int) request()->query('per_page', 0);
        $meta = null;
        if ($perPage > 0) {
            $perPage = min($perPage, 100);
            $page = max(1, (int) request()->query('page', 1));
            $all = collect($kajians)->values();
            $meta = [
                'total' => $all->count(),
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($all->count() / $perPage)),
            ];
            $kajians = $all->forPage($page, $perPage)->values();
        }

        $payload = [
            'status' => 'success',
            'data' => $kajians,
        ];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        $response = response()->json($payload);

        if (count($kajians) > 0) {
            $response->headers->set('Cache-Control', 'public, max-age=30, s-maxage=120, stale-while-revalidate=600, stale-if-error=86400');
            $response->headers->set('Vercel-CDN-Cache-Control', 'public, s-maxage=120, stale-while-revalidate=600, stale-if-error=86400');
        } else {
            $response->headers->set('Cache-Control', 'no-store');
        }

        return $response;
    }

    /**
     * Tampilkan peta interaktif sebaran majelis ilmu.
     */
    /**
     * Halaman khusus ustadz: rangkuman seluruh kajian dari satu ustadz.
     */
    public function ustadz(string $name): View
    {
        $name = urldecode($name);
        $service = new SupabaseKajianService();
        $kajians = collect();

        if ($service->isConfigured()) {
            $rows = $service->getActiveKajians();
            $kajians = collect($rows)
                ->filter(fn($r) => strcasecmp(trim($r['ustadz'] ?? ''), trim($name)) === 0)
                ->map(fn($r) => SupabaseKajianService::toObject($r))
                ->sortBy('event_datetime')
                ->values();
        }

        return view('kajian.ustadz', [
            'ustadzName' => $name,
            'kajians' => $kajians,
        ]);
    }

    public function map(): View
    {
        $service = new SupabaseKajianService();

        if ($service->isConfigured()) {
            $rows = $service->getActiveKajians();
            $kajians = SupabaseKajianService::toCollection($rows);
        } else {
            Kajian::purgeExpiredKajians(3);
            $cutoff = Carbon::now('Asia/Jakarta')->subHours(3);
            $kajians = Kajian::where('verified', true)
                ->where(function ($q) use ($cutoff) {
                    $q->where('event_datetime', '>=', $cutoff)
                      ->orWhere('is_rutin', true);
                })
                ->orderBy('event_datetime', 'asc')
                ->get();
        }

        return view('kajian.map', [
            'events' => $kajians,
            'kajians' => $kajians,
            'allKajiansJson' => $kajians->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
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

        $savedKajianIds = [];
        $attendedKajianIds = [];
        if ($user) {
            $appId = $this->appUserId() ?? $user->id;
            $allBookmarks = $this->getUserBookmarks($appId);
            $savedKajianIds = $allBookmarks->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
            // Filter berbasis TANGGAL (selaras profil): kajian tersimpan
            // tampil sepanjang hari tanggalnya. Perbandingan lama
            // (event_datetime berisi tanggal saja vs cutoff jam) rapuh
            // dan membuang kajian hari ini secara tak konsisten.
            $todayStr = Carbon::now('Asia/Jakarta')->toDateString();
            $bookmarkedKajians = $allBookmarks
                ->filter(function ($k) use ($todayStr) {
                    return $k->is_rutin || empty($k->event_date) || $k->event_date >= $todayStr;
                });
            // Kehadiran dibaca dari SUPABASE (tempat logAttendance menulis).
            // Cara lama membaca relasi SQLite lokal yang selalu kosong di
            // instance Vercel -> "Riwayat Hadir (0)" selamanya padahal data
            // tercatat (keluhan user 2026-10-20).
            $attData = $this->fetchAttendances($appId);
            if ($attData !== null) {
                [$attendances, $attendedKajianIds] = $attData;
            } else {
                $attendances = $user->attendances()->with('kajian')->latest()->get();
                $attendedKajianIds = $attendances
                    ->filter(fn ($a) => ($a->attendance_date ?? null) === Carbon::now('Asia/Jakarta')->toDateString())
                    ->map(fn ($a) => (int) $a->kajian_id)
                    ->values()->all();
            }
        }

        return view('kajian.saved', [
            'savedKajians' => $bookmarkedKajians,
            'bookmarkedKajians' => $bookmarkedKajians,
            'attendances' => $attendances,
            'savedKajianIds' => $savedKajianIds,
            'attendedKajianIds' => $attendedKajianIds,
        ]);
    }

    /**
     * Ambil riwayat kehadiran user dari Supabase (sumber kebenaran yang
     * sama dengan tempat logAttendance menulis), lengkap dengan data
     * kajian + daftar kajian_id yang sudah dihadiri HARI INI.
     * Return [Collection $attendances, array $attendedTodayIds] atau
     * null bila Supabase tak terkonfigurasi/gagal (pemanggil fallback).
     */
    private function fetchAttendances(int $userId): ?array
    {
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');
        if (!$supabaseUrl || !$supabaseKey) {
            return null;
        }
        $headers = [
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
        ];
        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(10)
                ->get("{$supabaseUrl}/rest/v1/attendances", [
                    'user_id' => "eq.{$userId}",
                    'select' => 'kajian_id,attendance_date',
                    'order' => 'attendance_date.desc',
                ]);
            if (!$res->successful()) {
                return null;
            }
            $rows = $res->json() ?? [];
            $ids = array_values(array_unique(array_map('intval', array_column($rows, 'kajian_id'))));
            $kajianById = [];
            if (!empty($ids)) {
                $kres = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(10)
                    ->get("{$supabaseUrl}/rest/v1/kajian", [
                        'id' => 'in.(' . implode(',', $ids) . ')',
                        'select' => '*',
                    ]);
                if ($kres->successful()) {
                    foreach (SupabaseKajianService::toCollection($kres->json() ?? []) as $k) {
                        $kajianById[(int) $k->id] = $k;
                    }
                }
            }
            $today = Carbon::now('Asia/Jakarta')->toDateString();
            $atts = collect();
            $todayIds = [];
            foreach ($rows as $r) {
                $kid = (int) $r['kajian_id'];
                $atts->push((object) [
                    'kajian' => $kajianById[$kid] ?? null,
                    'kajian_id' => $kid,
                    'attendance_date' => $r['attendance_date'] ?? null,
                ]);
                if (($r['attendance_date'] ?? null) === $today) {
                    $todayIds[] = $kid;
                }
            }
            return [$atts, $todayIds];
        } catch (\Throwable $e) {
            \Log::warning('Supabase fetch attendances gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ID user untuk SEMUA operasi tabel bersama Supabase (bookmark,
     * follow, attendance): ID app_users yang stabil via email.
     * Fallback ke Auth::id() HANYA bila Supabase nonaktif/gagal —
     * lihat SupabaseUserService::currentAppUserId() untuk alasannya
     * (Auth::id() tidak stabil antar instance Vercel).
     */
    private function appUserId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }
        try {
            $svc = new \App\Services\SupabaseUserService();
            if ($svc->enabled()) {
                $id = $svc->currentAppUserId();
                if ($id) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Resolve app user id gagal: ' . $e->getMessage());
        }
        return (int) Auth::id();
    }

    /**
     * Ambil daftar kajian_id bookmark user dari Supabase (persisten);
     * fallback ke SQLite lokal bila Supabase tak terkonfigurasi/gagal.
     */
    private function getUserBookmarkIds(int $userId): array
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
                if ($res->successful()) {
                    return array_map('intval', array_column($res->json() ?? [], 'kajian_id'));
                }
            } catch (\Throwable $e) {
                \Log::warning('Supabase get bookmark ids gagal: ' . $e->getMessage());
            }
        }

        return Bookmark::where('user_id', $userId)->pluck('kajian_id')->map(fn ($v) => (int) $v)->toArray();
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
                $ids = array_map('intval', array_column($rows ?? [], 'kajian_id'));
                if (!empty($ids)) {
                    $sbKajianSvc = new SupabaseKajianService();
                    if ($sbKajianSvc->isConfigured()) {
                        $allActive = $sbKajianSvc->getActiveKajians();
                        $matched = array_values(array_filter($allActive, fn($k) => in_array((int)($k['id'] ?? 0), $ids, true)));
                        if (!empty($matched)) {
                            return SupabaseKajianService::toCollection($matched);
                        }
                    }
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
        // 1. Honeypot check (bot trap)
        if ($request->filled('website_trap')) {
            return back()->with('success', 'Usulan jadwal kajian berhasil diajukan dan akan ditinjau oleh admin.');
        }

        // 2. Rate Limiting pengajuan
        $key = 'kajian-store-ip:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->withErrors([
                'title' => "Terlalu banyak pengajuan jadwal dalam waktu singkat. Silakan tunggu {$seconds} detik.",
            ])->withInput();
        }

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

        \Illuminate\Support\Facades\RateLimiter::hit($key, 900); // 15 menit

        // 3. Sanitasi input dari potensi XSS
        foreach (['title', 'ustadz', 'masjid', 'address', 'category', 'time', 'description'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = strip_tags(trim($validated[$field]));
            }
        }

        $validated['verified'] = false;
        $validated['status'] = 'aktif';
        $validated['source'] = 'user_submission';
        $validated['user_id'] = Auth::id();

        $kajian = Kajian::create($validated);

        // Dual-sync to Supabase if configured
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_role_key') ?: config('services.supabase.key');
        if ($supabaseUrl && $supabaseKey) {
            try {
                Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=minimal',
                ])->timeout(5)->post("{$supabaseUrl}/rest/v1/kajian", [
                    'title' => $kajian->title,
                    'ustadz' => $kajian->ustadz,
                    'category' => $kajian->category,
                    'audience' => $kajian->audience,
                    'masjid' => $kajian->masjid,
                    'address' => $kajian->address,
                    'lat' => $kajian->lat,
                    'lon' => $kajian->lon,
                    'day' => $kajian->day,
                    'time' => $kajian->time,
                    'description' => $kajian->description,
                    'source_url' => $kajian->source_url,
                    'verified' => false,
                    'status' => 'aktif',
                    'source' => 'user_submission',
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Supabase sync user submission failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Jadwal kajian berhasil diajukan dan sedang dalam antrean verifikasi tim moderasi.');
    }

    /**
     * Toggle bookmark kajian.
     * Menerima $id mentah (bukan route-model-binding) karena kajian
     * berasal dari Supabase, bukan SQLite lokal yang kosong di Vercel.
     */
    public function toggleBookmark(Request $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
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

        // Validasi kajian ada di Supabase (atau SQLite fallback)
        $kajianId = (int) $id;
        $sbKajianSvc = new SupabaseKajianService();
        $kajianExists = false;
        if ($sbKajianSvc->isConfigured()) {
            $kajianExists = (bool) $sbKajianSvc->find($kajianId);
        } else {
            $kajianExists = Kajian::where('id', $kajianId)->exists();
        }
        if (!$kajianExists) {
            if (!$request->wantsJson() && !$request->ajax()) {
                return back()->with('error', 'Kajian tidak ditemukan.');
            }
            return response()->json(['status' => 'error', 'message' => 'Kajian tidak ditemukan.'], 404);
        }

        $userId = $this->appUserId();
        $saved = false;
        $supabaseDone = false;

        // 1. Supabase adalah SUMBER KEBENARAN bookmark (SQLite Vercel ephemeral).
        //    Hasil toggle Supabase-lah yang dikembalikan ke klien.
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
                if (!$res->successful()) {
                    throw new \Exception('Cek bookmark Supabase HTTP ' . $res->status());
                }
                $existing = $res->json();

                if (!empty($existing)) {
                    // Filter WAJIB di query string URL (PostgREST tidak membaca body DELETE)
                    $del = \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(10)
                        ->delete("{$supabaseUrl}/rest/v1/bookmarks?user_id=eq.{$userId}&kajian_id=eq.{$kajianId}");
                    if (!$del->successful()) {
                        throw new \Exception('Hapus bookmark Supabase HTTP ' . $del->status());
                    }
                    $saved = false;
                } else {
                    $ins = \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'resolution=ignore-duplicates, return=minimal', 'Content-Type' => 'application/json']))
                        ->timeout(10)
                        ->post("{$supabaseUrl}/rest/v1/bookmarks", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                        ]);
                    if (!$ins->successful()) {
                        throw new \Exception('Simpan bookmark Supabase HTTP ' . $ins->status());
                    }
                    $saved = true;
                }
                $supabaseDone = true;
            } catch (\Throwable $e) {
                \Log::warning('Supabase bookmark gagal, fallback ke lokal: ' . $e->getMessage());
            }
        }

        // 2. SQLite lokal: fallback (saat Supabase tak terkonfigurasi/gagal) atau
        //    mirror pasif. TIDAK PERNAH menimpa hasil Supabase — bug lama di sini
        //    membuat $saved selalu mengikuti SQLite ephemeral sehingga jawaban
        //    server membatalkan klik user di klien (tombol terasa tidak bisa diklik).
        try {
            $existingLocal = Bookmark::where('user_id', $userId)
                ->where('kajian_id', $kajianId)
                ->first();

            if ($supabaseDone) {
                if ($saved && !$existingLocal) {
                    Bookmark::create([
                        'user_id' => $userId,
                        'kajian_id' => $kajianId,
                    ]);
                } elseif (!$saved && $existingLocal) {
                    $existingLocal->delete();
                }
            } else {
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
            }
        } catch (\Throwable $e) {
            \Log::warning('Bookmark lokal gagal (diabaikan): ' . $e->getMessage());
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
        // OTORITATIF SERVER: endpoint ini TIDAK LAGI memasukkan bookmark
        // dari daftar ID kiriman klien. Pola lama (localStorage -> server)
        // mengubah bookmark hantu di HP menjadi data server beneran dan
        // membuat angka profil phantom. Kini endpoint hanya mengembalikan
        // daftar bookmark user dari Supabase agar klien menyelaraskan
        // localStorage-nya KE server.
        if (!Auth::check()) {
            return response()->json(['status' => 'unauthenticated', 'synced' => 0, 'ids' => []], 401);
        }

        $userId = $this->appUserId();
        $ids = $userId ? $this->getUserBookmarkIds($userId) : [];

        return response()->json([
            'status' => 'success',
            'synced' => 0,
            'ids' => $ids,
            'total' => count($ids),
        ]);
    }

    /**
     * Catat kehadiran kajian (RSVP).
     * Menerima $id mentah (bukan route-model-binding) karena kajian
     * berasal dari Supabase, bukan SQLite lokal yang kosong di Vercel.
     */
    public function logAttendance(Request $request, $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'auth_required',
                'message' => 'Silakan masuk untuk menyimpan agenda ke akun Anda.',
            ], 401);
        }

        // Validasi kajian ada di Supabase (atau SQLite fallback)
        $kajianId = (int) $id;
        $sbKajianSvc = new SupabaseKajianService();
        $kajianExists = false;
        if ($sbKajianSvc->isConfigured()) {
            $kajianExists = (bool) $sbKajianSvc->find($kajianId);
        } else {
            $kajianExists = Kajian::where('id', $kajianId)->exists();
        }
        if (!$kajianExists) {
            return response()->json(['status' => 'error', 'message' => 'Kajian tidak ditemukan.'], 404);
        }

        $userId = $this->appUserId();
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
                    // Batalkan kehadiran. Filter WAJIB di query string URL:
                    // PostgREST TIDAK membaca filter dari body DELETE —
                    // pola lama berisiko menghapus baris tanpa filter.
                    $del = \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(10)
                        ->delete("{$supabaseUrl}/rest/v1/attendances?user_id=eq.{$userId}&kajian_id=eq.{$kajianId}&attendance_date=eq.{$today}");
                    if (!$del->successful()) {
                        throw new \Exception('Hapus attendance Supabase HTTP ' . $del->status());
                    }
                    $attended = false;
                } else {
                    // Catat kehadiran
                    $ins = \Illuminate\Support\Facades\Http::withHeaders(array_merge($headers, ['Prefer' => 'return=minimal', 'Content-Type' => 'application/json']))
                        ->timeout(10)
                        ->post("{$supabaseUrl}/rest/v1/attendances", [
                            'user_id' => $userId,
                            'kajian_id' => $kajianId,
                            'attendance_date' => $today,
                        ]);
                    if (!$ins->successful()) {
                        throw new \Exception('Simpan attendance Supabase HTTP ' . $ins->status());
                    }
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
     * Bersihkan SELURUH riwayat kehadiran user (tombol "Bersihkan Riwayat
     * Hadir" di tab Riwayat Hadir — perintah user 2026-10-20 agar ia bisa
     * menghapus sendiri histori kajian yang sudah dihadiri).
     * Supabase adalah sumber kebenaran; mirror SQLite lokal ikut
     * dibersihkan best-effort. Gagal di Supabase = error jujur, bukan
     * sukses palsu.
     */
    public function clearAttendances(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'auth_required',
                'message' => 'Silakan masuk untuk membersihkan riwayat kehadiran.',
            ], 401);
        }

        $userId = $this->appUserId();
        $cleared = 0;
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if ($supabaseUrl && $supabaseKey && $userId) {
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                        'Prefer' => 'return=representation',
                    ])
                    ->timeout(10)
                    ->delete("{$supabaseUrl}/rest/v1/attendances?user_id=eq.{$userId}");
                if (!$res->successful()) {
                    throw new \Exception('Bersihkan attendance Supabase HTTP ' . $res->status());
                }
                $cleared = count($res->json() ?? []);
            } catch (\Throwable $e) {
                \Log::warning('Supabase clear attendances gagal: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal membersihkan riwayat kehadiran di server.',
                ], 500);
            }
        }

        try {
            Attendance::where('user_id', Auth::id())->delete();
            if ($userId && (int) $userId !== (int) Auth::id()) {
                Attendance::where('user_id', $userId)->delete();
            }
        } catch (\Throwable $e) {
            // best effort saja
        }

        return response()->json([
            'status' => 'success',
            'cleared' => $cleared,
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

        $userId = $this->appUserId();
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
                    // Filter WAJIB di query string: body DELETE diabaikan
                    // PostgREST → tanpa ini, unfollow menghapus SELURUH
                    // baris majelis_follows (bug C2, audit 2026-10-21).
                    \Illuminate\Support\Facades\Http::withHeaders($headers)
                        ->timeout(6)
                        ->delete("{$supabaseUrl}/rest/v1/majelis_follows?user_id=eq.{$userId}&masjid=eq." . urlencode($masjid));
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
