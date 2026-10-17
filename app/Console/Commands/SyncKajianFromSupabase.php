<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SyncKajianFromSupabase extends Command
{
    protected $signature = 'kajian:sync-supabase';
    protected $description = 'Sync kajian dari Supabase ke SQLite lokal';

    public function handle(): int
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_key');

        if (!$url || !$key) {
            $this->error('SUPABASE_URL atau SUPABASE_SERVICE_KEY belum diset di .env');
            return 1;
        }

        $this->info('Mengambil data dari Supabase...');

        $response = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer ' . $key,
        ])->timeout(60)->get("{$url}/rest/v1/kajian", [
            'select' => '*',
            'verified' => 'eq.true',
            'order' => 'id',
        ]);

        if (!$response->successful()) {
            $this->error('Gagal mengambil data: ' . $response->status());
            return 1;
        }

        $rows = $response->json();
        $this->info('Ditemukan ' . count($rows) . ' kajian');

        $now = now()->toDateTimeString();
        $synced = 0;

        DB::transaction(function () use ($rows, $now, &$synced) {
            foreach ($rows as $r) {
                DB::table('kajians')->updateOrInsert(
                    ['id' => $r['id']],
                    [
                        'title' => $r['title'] ?? '',
                        'ustadz' => $r['ustadz'] ?? '',
                        'category' => $r['category'] ?? 'Kajian',
                        'audience' => $r['audience'] ?? 'Umum',
                        'masjid' => $r['masjid'] ?? '',
                        'address' => $r['address'] ?? '',
                        'lat' => $r['lat'] ?? 0,
                        'lon' => $r['lon'] ?? 0,
                        'day' => $r['day'] ?? '',
                        'time' => $r['time'] ?? '',
                        'description' => $r['description'] ?? '',
                        'verified' => $r['verified'] ? 1 : 0,
                        'source' => $r['source'] ?? 'instagram',
                        'source_url' => $r['source_url'] ?? '',
                        'poster_url' => $r['flyer_url'] ?? '',
                        'updated_at' => $now,
                    ]
                );
                $synced++;
            }

            // Hapus yang sudah tidak ada di Supabase (expired)
            $ids = collect($rows)->pluck('id')->toArray();
            DB::table('kajians')->whereNotIn('id', $ids)->delete();
        });

        // Clear cache
        Cache::forget('kajian_categories');
        Cache::forget('kajian_map_json');

        // Rebuild FTS index
        DB::statement('DELETE FROM kajians_fts');
        DB::statement('INSERT INTO kajians_fts(rowid, title, ustadz, masjid, address) SELECT id, title, ustadz, masjid, address FROM kajians');

        $this->info("Sync selesai: {$synced} kajian");
        return 0;
    }
}
