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
     * Ambil semua kajian aktif dari Supabase (dengan cache 5 menit).
     */
    public function getActiveKajians(): array
    {
        return Cache::remember('supabase_kajians_active', 300, function () {
            try {
                $res = Http::withHeaders($this->headers())
                    ->timeout(15)
                    ->get("{$this->url}/rest/v1/kajian", [
                        'select' => 'id,title,ustadz,category,audience,masjid,address,lat,lon,day,time,description,verified,source,source_url,flyer_url,event_date',
                        'verified' => 'eq.true',
                        'order' => 'event_date.asc.nullslast,id.desc',
                        'limit' => 500,
                    ]);

                if ($res->successful()) {
                    $rows = $res->json() ?? [];
                    // Filter: hanya yang event_date >= hari ini atau null (rutin)
                    $today = now('Asia/Jakarta')->toDateString();
                    return array_values(array_filter($rows, function ($r) use ($today) {
                        $ed = $r['event_date'] ?? null;
                        return empty($ed) || $ed >= $today;
                    }));
                }
            } catch (\Exception $e) {
                Log::warning('SupabaseKajianService gagal: ' . $e->getMessage());
            }
            return [];
        });
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
                    'select' => 'id,title,ustadz,category,audience,masjid,address,lat,lon,day,time,description,verified,source,source_url,flyer_url,event_date',
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
        Cache::forget('supabase_kajians_active');
    }

    /**
     * Konversi array Supabase ke object agar kompatibel dengan Blade.
     * Map flyer_url -> poster_url, event_date -> event_datetime.
     */
    public static function toObject(array $row): object
    {
        return (object)[
            'id' => $row['id'],
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
