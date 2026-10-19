<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\SupabaseKajianService;

class PushKajianToSupabase extends Command
{
    protected $signature = 'kajian:push-supabase';
    protected $description = 'Upload / sync seluruh kajian dari SQLite lokal ke Supabase REST API';

    public function handle(): int
    {
        $url = config('services.supabase.url') ?: env('SUPABASE_URL') ?: 'https://hhcopgprjdjvgfcuqdyf.supabase.co';
        $key = config('services.supabase.service_key') ?: env('SUPABASE_SERVICE_KEY') ?: env('SUPABASE_KEY');

        if (!$key) {
            $this->error('SUPABASE_SERVICE_KEY belum diset. Harap sediakan API Key Supabase.');
            return 1;
        }

        $kajians = DB::table('kajians')->orderBy('id')->get();
        $this->info("Menemukan {$kajians->count()} kajian di SQLite lokal. Memulai pengunggahan ke Supabase...");

        $headers = [
            'apikey' => $key,
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
            'Prefer' => 'resolution=merge-duplicates',
        ];

        $chunks = $kajians->chunk(25);
        $totalUploaded = 0;

        foreach ($chunks as $index => $chunk) {
            $payload = [];
            foreach ($chunk as $k) {
                $payload[] = [
                    'id' => (int)$k->id,
                    'title' => (string)($k->title ?? ''),
                    'ustadz' => (string)($k->ustadz ?? ''),
                    'category' => (string)($k->category ?? 'Kajian'),
                    'audience' => (string)($k->audience ?? 'Umum'),
                    'masjid' => (string)($k->masjid ?? ''),
                    'address' => (string)($k->address ?? ''),
                    'lat' => (float)($k->lat ?? 0),
                    'lon' => (float)($k->lon ?? 0),
                    'day' => (string)($k->day ?? ''),
                    'time' => (string)($k->time ?? ''),
                    'description' => (string)($k->description ?? ''),
                    'verified' => (bool)($k->verified ?? true),
                    'source' => in_array($k->source ?? '', ['instagram', 'facebook', 'threads']) ? $k->source : 'instagram',
                    'source_url' => (string)($k->source_url ?? ''),
                    'flyer_url' => (string)($k->poster_url ?? ($k->flyer_url ?? '')),
                    'event_date' => !empty($k->event_date) ? $k->event_date : null,
                ];
            }

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$url}/rest/v1/kajian", $payload);

            if ($response->successful()) {
                $totalUploaded += count($payload);
                $this->info("Batch " . ($index + 1) . " (" . count($payload) . " kajian) berhasil diunggah.");
            } else {
                $this->warn("Batch " . ($index + 1) . " gagal: HTTP " . $response->status() . " - " . $response->body());
            }
        }

        SupabaseKajianService::clearCache();
        $this->info("Selesai! Total {$totalUploaded} kajian berhasil disinkronkan ke Supabase.");
        return 0;
    }
}
