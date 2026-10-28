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
use Illuminate\Support\Facades\Http;
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
     * Titik lokasi default (Bandung) — SAMA dengan fallback JS di
     * index.blade.php saat belum ada lokasi tersimpan / GPS. Dipakai
     * serverFirstCards agar urutan kartu server = urutan render pertama JS.
     */
    private const DEFAULT_LAT = -6.9175;
    private const DEFAULT_LON = 107.6191;
    private const DEFAULT_RADIUS_KM = 25;

    /**
     * Baca satu field dari item koleksi (stdClass Supabase atau model Eloquent).
     */
    private function kfield($item, string $key, $default = null)
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }
        if (is_object($item)) {
            return $item->{$key} ?? $default;
        }
        return $default;
    }

    /**
     * Satu item -> array ringkas untuk JSON beranda. Sengaja DIBUANG:
     * flyer_url (duplikat poster_url), is_rutin, verified (tak dipakai FE),
     * description (diambil on-demand via apiShow saat modal dibuka).
     */
    private function slimKajianItem($item): array
    {
        $date = $this->kfield($item, 'event_date');
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        } elseif (is_string($date) && strlen($date) > 10) {
            $date = substr($date, 0, 10);
        }
        $dt = $this->kfield($item, 'event_datetime', $date);
        if ($dt instanceof \DateTimeInterface) {
            $dt = $dt->format('Y-m-d');
        } elseif (is_string($dt) && strlen($dt) > 10) {
            $dt = substr($dt, 0, 10);
        }

        return [
            'id' => (int) $this->kfield($item, 'id', 0),
            'title' => (string) $this->kfield($item, 'title', ''),
            'ustadz' => (string) $this->kfield($item, 'ustadz', ''),
            'masjid' => (string) $this->kfield($item, 'masjid', ''),
            'address' => (string) $this->kfield($item, 'address', ''),
            'day' => (string) $this->kfield($item, 'day', ''),
            'event_date' => $date ?: null,
            'event_datetime' => $dt ?: null,
            'time' => (string) $this->kfield($item, 'time', ''),
            'category' => (string) $this->kfield($item, 'category', 'Kajian'),
            'audience' => (string) $this->kfield($item, 'audience', 'Umum'),
            'lat' => (float) $this->kfield($item, 'lat', 0),
            'lon' => (float) $this->kfield($item, 'lon', 0),
            'poster_url' => $this->kfield($item, 'poster_url'),
            'source' => (string) $this->kfield($item, 'source', 'instagram'),
            'source_url' => (string) $this->kfield($item, 'source_url', ''),
            'is_cancelled' => (bool) $this->kfield($item, 'is_cancelled', false),
        ];
    }

    private function slimKajianPayload($collection): array
    {
        return collect($collection)->map(fn($item) => $this->slimKajianItem($item))->values()->all();
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /**
     * 12 kartu pertama tampilan default beranda (radius 25 km dari titik
     * default, urut tanggal -> jarak -> jam — sama persis dengan
     * renderKajianFeed() di JS). Dihitung dari koleksi yang SUDAH diambil
     * (tanpa query tambahan — nol beban server ekstra).
     */
    private function serverFirstCards($collection): array
    {
        $items = [];
        foreach ($collection as $item) {
            $lat = (float) $this->kfield($item, 'lat', 0);
            $lon = (float) $this->kfield($item, 'lon', 0);
            if (!$lat || !$lon) {
                continue;
            }
            $dist = $this->haversineKm(self::DEFAULT_LAT, self::DEFAULT_LON, $lat, $lon);
            if ($dist > self::DEFAULT_RADIUS_KM) {
                continue;
            }
            $slim = $this->slimKajianItem($item);
            // Presisi penuh untuk urut (JS mengurut dengan jarak presisi
            // penuh juga); pembulatan 1 desimal hanya saat tampil di Blade.
            $slim['distance'] = $dist;
            $items[] = $slim;
        }
        usort($items, function ($a, $b) {
            $da = $a['event_date'] ?? '9999-12-31';
            $db = $b['event_date'] ?? '9999-12-31';
            if ($da !== $db) {
                return $da <=> $db;
            }
            if ($a['distance'] !== $b['distance']) {
                return $a['distance'] <=> $b['distance'];
            }
            return ($a['time'] ?? '') <=> ($b['time'] ?? '');
        });
        return array_slice($items, 0, 12);
    }

    /**
     * Index via Supabase REST API.
     */
    protected function indexFromSupabase(Request $request, SupabaseKajianService $service): View
    {
        $allRows = $service->getActiveKajians();
        $collection = SupabaseKajianService::toCollection($allRows);

        $categories = $service->getCategories();

        // JSON ringkas untuk feed client-side (semua, tanpa filter/pagination)
        // — dari hasil yang sama, tanpa API call ke-2. Slim (B+C 2026-10-26):
        // tanpa flyer_url/is_rutin/verified/description (~29% lebih kecil).
        $mapJson = json_encode($this->slimKajianPayload($collection), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $serverCards = $this->serverFirstCards($collection);
        // Spotlight = 3 pertama tampilan default (sama dengan slice(0,3)
        // hasil hitung JS) — dirender server agar gambar LCP ketemu
        // saat parse HTML, bukan setelah JS jalan.
        $serverSpotlight = array_slice($serverCards, 0, 3);

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
            'serverCards' => $serverCards,
            'serverSpotlight' => $serverSpotlight,
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
        $mapJson = json_encode($this->slimKajianPayload($mapKajians), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $serverCards = $this->serverFirstCards($mapKajians);
        $serverSpotlight = array_slice($serverCards, 0, 3);

        $userBookmarks = [];
        if (Auth::check()) {
            $userBookmarks = Bookmark::where('user_id', Auth::id())->pluck('kajian_id')->toArray();
        }

        return view('kajian.index', [
            'kajians' => $kajians,
            'allKajiansJson' => $mapJson,
            'categories' => $categories,
            'userBookmarks' => $userBookmarks,
            'serverCards' => $serverCards,
            'serverSpotlight' => $serverSpotlight,
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
     * Detail ringkas satu kajian (B+C 2026-10-26): hanya id + description,
     * dipakai modal detail untuk mengisi deskripsi yang TIDAK lagi dibawa
     * JSON beranda. Sama seperti apiList: respons di-cache CDN (s-maxage),
     * jadi origin hanya menerima <=1 request per masa s-maxage per region.
     */
    public function apiShow(int $id): JsonResponse
    {
        $service = new SupabaseKajianService();
        $row = null;
        if ($service->isConfigured()) {
            $row = $service->find($id);
        } else {
            $k = Kajian::find($id);
            if ($k) {
                $row = ['id' => $k->id, 'description' => $k->description];
            }
        }
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Kajian tidak ditemukan.'], 404);
        }
        $response = response()->json(['status' => 'success', 'data' => [
            'id' => (int) ($row['id'] ?? $id),
            'description' => (string) ($row['description'] ?? ''),
        ]]);
        $response->headers->set('Cache-Control', 'public, max-age=30, s-maxage=120, stale-while-revalidate=600, stale-if-error=86400');
        $response->headers->set('Vercel-CDN-Cache-Control', 'public, s-maxage=120, stale-while-revalidate=600, stale-if-error=86400');
        return $response;
    }

    /**
     * Proxy thumbnail flyer lewat edge Vercel (LCP fix 2026-10-27).
     * Supabase Storage melayani gambar dengan `Cache-Control: private,
     * no-cache` (TERBUKTI: metadata cacheControl max-age=31536000 tetap
     * disajikan no-cache, CF-Cache-Status: DYNAMIC) sehingga CDN tidak
     * pernah meng-cache gambar — TTFB gambar ~1,5-2 dtk, itulah selisih
     * FCP 1,1s -> LCP 2,6s. Route ini mengambil transform Supabase
     * (resize+webp) dari server (sin1, satu region dengan Supabase) lalu
     * mengembalikannya dengan header cache panjang — edge Vercel meng-cache
     * respons (s-maxage) sehingga request berikutnya adalah edge HIT.
     * Flyer bersifat imutabel per id, jadi TTL panjang aman. Bila Supabase
     * tidak punya flyer itu -> 404 (img onerror di view jatuh ke URL
     * transform langsung, lalu sembunyikan).
     */
    public function thumb(string $id, string $w): \Illuminate\Http\Response
    {
        // id harus numerik: host sudah tetap (Supabase), jadi tidak ada SSRF.
        if (!ctype_digit($id)) {
            abort(404);
        }
        $width = (int) $w;
        if ($width < 100) {
            $width = 100;
        } elseif ($width > 1200) {
            $width = 1200;
        }

        $base = rtrim(config('services.supabase.url') ?: 'https://hhcopgprjdjvgfcuqdyf.supabase.co', '/');
        $src = $base . '/storage/v1/render/image/public/flyers/' . $id . '.jpg'
            . '?width=' . $width . '&quality=60&format=webp&resize=cover';

        try {
            $res = Http::timeout(10)->get($src);
        } catch (\Throwable $e) {
            abort(502);
        }
        if (!$res->successful()) {
            // Supabase mengembalikan 400 (bukan 404) bila objek tidak ada.
            abort(in_array($res->status(), [400, 404], true) ? 404 : 502);
        }
        $body = $res->body();
        if ($body === '' || $body === null) {
            abort(502);
        }
        $response = response($body, 200, ['Content-Type' => 'image/webp']);
        $response->headers->set('Cache-Control', 'public, max-age=86400, immutable');
        $response->headers->set('Vercel-CDN-Cache-Control', 's-maxage=31536000, stale-while-revalidate=86400');
        return $response;
    }

    /**
     * Suara adzan via proxy edge (2026-10-29): HP user gagal memutar
     * MP3 langsung dari Supabase (jaringan ke supabase.co diblokir
     * atau diulur), padahal server (sin1) mencapai Supabase dengan
     * baik — pola yang sama dengan thumb. Header Range diteruskan
     * ke Supabase agar pemutar iOS (AVPlayer, memakai Range) tetap
     * bisa streaming; respons 206 dikembalikan apa adanya. Nama
     * file di-allowlist (24 suara) agar route ini tidak bisa dipakai
     * mengambil objek lain dari bucket.
     */
    public function adzan(string $file): \Illuminate\Http\Response
    {
        $allowed = ['a9', 'a1', 'a2', 'a3', 'a4', 'a7',
            'm-ali-mullah', 'm-basnawi', 'm-naif', 'm-majid', 'm-maghribi', 'm-hamd',
            'm-hashim', 'm-hussain', 'm-emad', 'm-khoja', 'm-saeed', 'm-suhail', 'm-taufiq',
            'q-hijaz-kar', 'q-sika', 'q-husaini', 'q-jaharka', 'q-maya'];
        if (!in_array($file, $allowed, true)) {
            abort(404);
        }
        $base = rtrim(config('services.supabase.url') ?: 'https://hhcopgprjdjvgfcuqdyf.supabase.co', '/');
        $src = $base . '/storage/v1/object/public/adzan/' . $file . '.mp3';
        $headers = [];
        $range = request()->header('Range');
        if ($range) {
            $headers['Range'] = $range;
        }
        try {
            $res = Http::timeout(20)->withHeaders($headers)->get($src);
        } catch (\Throwable $e) {
            abort(502);
        }
        if (!$res->successful()) {
            abort(in_array($res->status(), [400, 404], true) ? 404 : 502);
        }
        $body = $res->body();
        if ($body === '' || $body === null) {
            abort(502);
        }
        $status = $res->status() === 206 ? 206 : 200;
        $response = response($body, $status, ['Content-Type' => 'audio/mpeg']);
        $response->headers->set('Accept-Ranges', 'bytes');
        if ($res->header('Content-Range')) {
            $response->headers->set('Content-Range', $res->header('Content-Range'));
        }
        if ($res->header('Content-Length')) {
            $response->headers->set('Content-Length', $res->header('Content-Length'));
        }
        $response->headers->set('Cache-Control', 'public, max-age=86400, immutable');
        $response->headers->set('Vercel-CDN-Cache-Control', 's-maxage=31536000, stale-while-revalidate=86400');
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
            'isFollowing' => $this->isFollowingUstadz($name),
            'followerCount' => $this->countUstadzFollowers($name),
        ]);
    }

    /** Apakah user sesi ini mengikuti ustadz tersebut (lokal/session). */
    private function isFollowingUstadz(string $name): bool
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('ustadz_follows')) {
            $userId = $this->appUserId();
            $query = \App\Models\UstadzFollow::where('ustadz', $name);
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_token', request()->session()->getId());
            }
            if ($query->exists()) {
                return true;
            }
        }
        return in_array($name, session()->get('followed_ustadz', []), true);
    }

    /** Jumlah pengikut ustadz (Supabase bila aktif, fallback lokal). */
    private function countUstadzFollowers(string $name): int
    {
        $svc = new \App\Services\SupabaseUserService();
        if ($svc->enabled()) {
            return count($svc->getUstadzFollowerUserIds($name));
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('ustadz_follows')) {
            return \App\Models\UstadzFollow::where('ustadz', $name)->count();
        }
        return 0;
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
     * Jadwal sholat hari ini (Segmen No.2, 2026-10-28).
     * Sumber: Aladhan API (gratis, tanpa key), metode 20 (Kemenag RI).
     * Lokasi: query ?lat&lon (dari geo engine) atau ?city; default
     * Bandung — sama dengan titik default beranda. Hasil di-cache 6 jam.
     */
    public function sholat(Request $request): View
    {
        $lat = $request->query('lat') !== null ? (float) $request->query('lat') : self::DEFAULT_LAT;
        $lon = $request->query('lon') !== null ? (float) $request->query('lon') : self::DEFAULT_LON;
        $city = trim((string) $request->query('city', 'Bandung'));

        $data = $this->fetchPrayerTimings($lat, $lon, $city);

        $prefs = null;
        $appId = $this->appUserId();
        if ($appId) {
            $svc = new \App\Services\SupabaseUserService();
            $prefs = $svc->enabled() ? $svc->getNotificationPrefs($appId) : null;
        }

        return view('kajian.sholat', [
            'timings' => $data['timings'] ?? [],
            'hijri' => $data['hijri'] ?? null,
            'gregorian' => $data['gregorian'] ?? null,
            'city' => $city,
            'lat' => $lat,
            'lon' => $lon,
            'prefs' => $prefs,
            'isLoggedIn' => (bool) $appId,
        ]);
    }

    /**
     * Dzikir pagi & petang — teks dari buku Ustadz Yazid bin Abdul
     * Qadir Jawas rahimahullah (perintah user 2026-10-28). Halaman
     * statis: tanpa login, tanpa DB — data dari App\Data\DzikirData.
     */
    public function dzikir(): View
    {
        return view('kajian.dzikir', [
            'pagi' => \App\Data\DzikirData::pagi(),
            'petang' => \App\Data\DzikirData::petang(),
        ]);
    }

    /**
     * JSON jadwal sholat — dipakai halaman /sholat saat GPS perangkat
     * tersedia (geo engine) agar waktu menyesuaikan lokasi akurat user
     * tanpa reload penuh.
     */
    public function apiSholat(Request $request): JsonResponse
    {
        $lat = $request->query('lat') !== null ? (float) $request->query('lat') : self::DEFAULT_LAT;
        $lon = $request->query('lon') !== null ? (float) $request->query('lon') : self::DEFAULT_LON;
        $city = trim((string) $request->query('city', 'Bandung'));

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return response()->json(['error' => 'Koordinat tidak valid.'], 422);
        }

        // ?date=YYYY-MM-DD opsional (maks. 2 hari ke depan — untuk
        // countdown Subuh besok saat semua sholat hari ini sudah lewat).
        $date = Carbon::now('Asia/Jakarta')->format('d-m-Y');
        if (is_string($request->query('date')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('date'))) {
            $req = Carbon::parse($request->query('date'), 'Asia/Jakarta');
            $diff = Carbon::now('Asia/Jakarta')->startOfDay()->diffInDays($req->startOfDay(), false);
            if ($diff >= 0 && $diff <= 2) {
                $date = $req->format('d-m-Y');
            }
        }

        return response()->json($this->fetchPrayerTimings($lat, $lon, $city, $date));
    }

    /**
     * Ambil jadwal sholat dari Aladhan (cache 6 jam per lokasi+tanggal).
     * Fallback: timingsByCity bila koordinat gagal.
     */
    private function fetchPrayerTimings(float $lat, float $lon, string $city, ?string $date = null): array
    {
        $date = $date ?: Carbon::now('Asia/Jakarta')->format('d-m-Y');
        $key = 'sholat:' . $date . ':' . round($lat, 2) . ':' . round($lon, 2);

        return Cache::remember($key, 6 * 3600, function () use ($lat, $lon, $city, $date) {
            $empty = ['timings' => [], 'hijri' => null, 'gregorian' => null];
            try {
                $res = Http::timeout(8)->get("https://api.aladhan.com/v1/timings/{$date}", [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'method' => 20,
                    'timezonestring' => 'Asia/Jakarta',
                ]);
                $json = $res->successful() ? $res->json() : null;
                if (empty($json['data']['timings'])) {
                    $res = Http::timeout(8)->get("https://api.aladhan.com/v1/timingsByCity/{$date}", [
                        'city' => $city,
                        'country' => 'Indonesia',
                        'method' => 20,
                        'timezonestring' => 'Asia/Jakarta',
                    ]);
                    $json = $res->successful() ? $res->json() : null;
                }
                if (empty($json['data']['timings'])) {
                    return $empty;
                }
                $t = $json['data']['timings'];
                $clean = function ($v) {
                    return substr(trim((string) $v), 0, 5);
                };
                $hijri = $json['data']['date']['hijri'] ?? null;
                $greg = $json['data']['date']['gregorian'] ?? null;
                return [
                    'timings' => [
                        'Imsak' => $clean($t['Imsak'] ?? ''),
                        'Subuh' => $clean($t['Fajr'] ?? ''),
                        'Terbit' => $clean($t['Sunrise'] ?? ''),
                        'Dzuhur' => $clean($t['Dhuhr'] ?? ''),
                        'Ashar' => $clean($t['Asr'] ?? ''),
                        'Maghrib' => $clean($t['Maghrib'] ?? ''),
                        'Isya' => $clean($t['Isha'] ?? ''),
                    ],
                    'hijri' => $hijri ? (($hijri['day'] ?? '') . ' ' . ($hijri['month']['en'] ?? '') . ' ' . ($hijri['year'] ?? '') . ' H') : null,
                    'gregorian' => $greg ? (($greg['day'] ?? '') . ' ' . ($greg['month']['en'] ?? '') . ' ' . ($greg['year'] ?? '')) : null,
                ];
            } catch (\Throwable $e) {
                \Log::warning('Aladhan fetch gagal: ' . $e->getMessage());
                return $empty;
            }
        });
    }

    /**
     * Simpan preferensi pengingat ibadah (adzan/dzikir/puasa) user login.
     * Disimpan di Supabase notification_prefs (SQL pendamping); bila
     * tabel belum ada, kembalikan 503 agar UI menjelaskan ke user.
     */
    public function saveNotifPrefs(Request $request): JsonResponse
    {
        $appId = $this->appUserId();
        if (!$appId) {
            return response()->json(['error' => 'Masuk dulu untuk mengaktifkan pengingat.'], 401);
        }

        $data = $request->validate([
            'adzan' => 'sometimes|boolean',
            'dzikir' => 'sometimes|boolean',
            'puasa' => 'sometimes|boolean',
            'city' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric|between:-90,90',
            'lon' => 'nullable|numeric|between:-180,180',
        ]);

        $svc = new \App\Services\SupabaseUserService();
        if (!$svc->enabled()) {
            return response()->json(['error' => 'Layanan tidak tersedia.'], 503);
        }

        $existing = $svc->getNotificationPrefs($appId) ?? [];
        $payload = [
            'user_id' => $appId,
            'adzan' => (bool) ($data['adzan'] ?? $existing['adzan'] ?? false),
            'dzikir' => (bool) ($data['dzikir'] ?? $existing['dzikir'] ?? false),
            'puasa' => (bool) ($data['puasa'] ?? $existing['puasa'] ?? false),
            'city' => $data['city'] ?? $existing['city'] ?? 'Bandung',
            'lat' => isset($data['lat']) ? (float) $data['lat'] : (isset($existing['lat']) ? (float) $existing['lat'] : self::DEFAULT_LAT),
            'lon' => isset($data['lon']) ? (float) $data['lon'] : (isset($existing['lon']) ? (float) $existing['lon'] : self::DEFAULT_LON),
        ];

        if (!$svc->upsertNotificationPrefs($payload)) {
            return response()->json(['error' => 'Gagal menyimpan pengingat di server. Coba lagi sebentar lagi.'], 503);
        }

        return response()->json(['status' => 'success', 'prefs' => $payload]);
    }

    /**
     * Ikuti / berhenti mengikuti ustadz (Segmen No.5, 2026-10-28).
     * Cermin toggleFollowMajelis: tabel lokal + session fallback +
     * sinkronisasi Supabase ustadz_follows bila user login.
     */
    public function toggleFollowUstadz(Request $request): \Illuminate\Http\JsonResponse
    {
        $ustadz = trim($request->input('ustadz', ''));
        if (empty($ustadz)) {
            return response()->json(['error' => 'Nama ustadz diperlukan.'], 422);
        }

        $userId = $this->appUserId();
        $sessionToken = $request->session()->getId();
        // Bila klien mengirim state yang diinginkan (follow: true/false),
        // patuhi itu — idempotent lintas perangkat. Bila tidak, toggle.
        $wantFollow = $request->has('follow') ? $request->boolean('follow') : null;
        $followed = false;

        if (\Illuminate\Support\Facades\Schema::hasTable('ustadz_follows')) {
            $query = \App\Models\UstadzFollow::where('ustadz', $ustadz);
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_token', $sessionToken);
            }
            $existing = $query->first();

            if ($wantFollow === null) {
                $followed = !$existing;
            } else {
                $followed = $wantFollow;
            }

            if ($followed && !$existing) {
                \App\Models\UstadzFollow::create([
                    'user_id' => $userId,
                    'session_token' => $sessionToken,
                    'ustadz' => $ustadz,
                ]);
            } elseif (!$followed && $existing) {
                $existing->delete();
            }
        }

        // Simpan juga ke Session untuk Vercel serverless fallback
        $sessionFollows = session()->get('followed_ustadz', []);
        if ($followed) {
            if (!in_array($ustadz, $sessionFollows)) {
                $sessionFollows[] = $ustadz;
            }
        } else {
            $sessionFollows = array_values(array_diff($sessionFollows, [$ustadz]));
        }
        session()->put('followed_ustadz', $sessionFollows);

        // Sinkronisasi Supabase jika aktif (hanya user login — butuh user_id stabil)
        $svc = new \App\Services\SupabaseUserService();
        if ($svc->enabled() && $userId) {
            try {
                if ($followed) {
                    $svc->upsertUstadzFollow(['user_id' => $userId, 'ustadz' => $ustadz]);
                } else {
                    $svc->deleteUstadzFollow($userId, $ustadz);
                }
            } catch (\Exception $e) {
                \Log::warning('Supabase follow ustadz gagal: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'followed' => $followed,
            'ustadz' => $ustadz,
        ]);
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
