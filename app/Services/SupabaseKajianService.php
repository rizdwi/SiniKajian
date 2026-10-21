<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk membaca data kajian langsung dari Supabase.
 * Menggantikan SQLite yang ephemeral di Vercel serverless.
 */
class SupabaseKajianService
{
    protected ?string $url = null;
    protected ?string $key = null;

    public function __construct()
    {
        $this->url = config('services.supabase.url') ?: env('SUPABASE_URL') ?: getenv('SUPABASE_URL') ?: null;
        $this->key = config('services.supabase.service_key')
            ?: config('services.supabase.service_role_key')
            ?: config('services.supabase.key')
            ?: env('SUPABASE_SERVICE_KEY')
            ?: env('SUPABASE_KEY')
            ?: getenv('SUPABASE_SERVICE_KEY')
            ?: null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->url) && !empty($this->key);
    }

    protected function headers(): array
    {
        return [
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];
    }

    /**
     * True bila pengambilan terakhir ke Supabase GAGAL (dibedakan dari
     * "sukses tapi memang kosong") — audit 2026-10-21, FE-H6. Kegagalan
     * TIDAK PERNAH masuk cache; sebagai gantinya salinan basi terakhir
     * (maks 24 jam) disajikan agar homepage tidak kosong senyap.
     */
    public bool $lastFetchFailed = false;

    /**
     * Memo per-proses (TTL 30 detik): satu request halaman memanggil
     * getActiveKajians() berkali-kali (konten + kategori + find) —
     * tanpa memo, blob cache sebesar daftar kajian diunduh dari REST
     * Supabase pada SETIAP panggilan (penyebab API 6-7 detik, 2026-10-21).
     */
    private static ?array $memoRows = null;
    private static int $memoAt = 0;
    private const MEMO_TTL = 30;

    /**
     * Ambil semua kajian aktif dari Supabase (dengan cache 5 menit).
     */
    public function getActiveKajians(): array
    {
        if (self::$memoRows !== null && (time() - self::$memoAt) < self::MEMO_TTL) {
            return self::$memoRows;
        }

        $cached = Cache::get('supabase_kajians_active');
        if (is_array($cached)) {
            $this->lastFetchFailed = false;
            self::$memoRows = $cached;
            self::$memoAt = time();
            return $cached;
        }

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/kajian", [
                    'select' => 'id,title,ustadz,category,audience,masjid,address,lat,lon,day,time,description,verified,source,source_url,flyer_url,event_date,is_cancelled',
                    'verified' => 'eq.true',
                    'order' => 'event_date.asc.nullslast,id.desc',
                    'limit' => 500,
                ]);

            if ($res->successful()) {
                $rows = $res->json() ?? [];
                // Filter: hanya yang event_date >= hari ini atau null (rutin)
                $today = now('Asia/Jakarta')->toDateString();
                $active = array_values(array_filter($rows, function ($r) use ($today) {
                    $ed = $r['event_date'] ?? null;
                    return empty($ed) || $ed >= $today;
                }));
                Cache::put('supabase_kajians_active', $active, 300);
                Cache::put('supabase_kajians_active_stale', $active, 86400);
                $this->lastFetchFailed = false;
                self::$memoRows = $active;
                self::$memoAt = time();
                return $active;
            }
            Log::warning('SupabaseKajianService HTTP ' . $res->status());
        } catch (\Exception $e) {
            Log::warning('SupabaseKajianService gagal: ' . $e->getMessage());
        }

        $this->lastFetchFailed = true;
        $stale = Cache::get('supabase_kajians_active_stale');
        if (is_array($stale) && $stale !== []) {
            self::$memoRows = $stale;
            self::$memoAt = time();
            return $stale;
        }
        return [];
    }

    /**
     * Ambil SEMUA kajian dari Supabase (termasuk yang belum verified) — untuk panel admin.
     * Tidak di-cache agar data moderasi selalu fresh.
     */
    public function getAllKajians(): array
    {
        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/kajian", [
                    'select' => 'id,title,ustadz,category,audience,masjid,address,lat,lon,day,time,description,verified,source,source_url,flyer_url,event_date,is_cancelled',
                    'order' => 'id.desc',
                    'limit' => 1000,
                ]);

            if ($res->successful()) {
                return $res->json() ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('SupabaseKajianService getAllKajians gagal: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Ambil satu kajian by ID.
     */
    public function find(int $id): ?array
    {
        $all = $this->getActiveKajians();
        foreach ($all as $k) {
            if ((int)($k['id'] ?? 0) === $id) {
                return $k;
            }
        }

        // Fallback: query langsung
        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/kajian", [
                    'id' => "eq.{$id}",
                    'verified' => 'eq.true',
                    'select' => 'id,title,ustadz,category,audience,masjid,address,lat,lon,day,time,description,verified,source,source_url,flyer_url,event_date,is_cancelled',
                ]);
            $rows = $res->json() ?? [];
            return $rows[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Ambil daftar kategori unik.
     */
    public function getCategories(): array
    {
        $all = $this->getActiveKajians();
        $cats = array_unique(array_filter(array_column($all, 'category')));
        sort($cats);
        return array_values($cats);
    }

    /**
     * Clear cache (dipanggil setelah sync).
     */
    public static function clearCache(): void
    {
        self::$memoRows = null;
        self::$memoAt = 0;
        Cache::forget('supabase_kajians_active');
    }

    /**
     * Konversi array Supabase ke object agar kompatibel dengan Blade.
     * Map flyer_url -> poster_url, event_date -> event_datetime.
     */
    public static function toObject(array $row): object
    {
        return (object)[
            'id' => $row['id'] ?? 0,
            'title' => $row['title'] ?? '',
            'ustadz' => $row['ustadz'] ?? '',
            'category' => $row['category'] ?? 'Kajian',
            'audience' => $row['audience'] ?? 'Umum',
            'masjid' => $row['masjid'] ?? '',
            'address' => $row['address'] ?? '',
            'lat' => (float)($row['lat'] ?? 0),
            'lon' => (float)($row['lon'] ?? 0),
            'day' => $row['day'] ?? '',
            'time' => $row['time'] ?? '',
            'description' => $row['description'] ?? '',
            'verified' => (bool)($row['verified'] ?? true),
            'source' => $row['source'] ?? 'instagram',
            'source_url' => $row['source_url'] ?? '',
            'poster_url' => $row['flyer_url'] ?? null,
            'flyer_url' => $row['flyer_url'] ?? null,
            'event_date' => $row['event_date'] ?? null,
            'event_datetime' => $row['event_date'] ?? null,
            'is_rutin' => empty($row['event_date']),
            'is_cancelled' => (bool)($row['is_cancelled'] ?? false),
        ];
    }

    /**
     * Konversi array of rows ke collection of objects.
     */
    public static function toCollection(array $rows)
    {
        return collect($rows)->map(fn($r) => self::toObject($r));
    }
}
