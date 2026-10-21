<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Beacon pencatat pageview first-party (tanpa pihak ketiga, tanpa cookie,
 * tanpa data pribadi — hanya path halaman). Dipakai menu admin Traffic.
 *
 * Menulis ke tabel Supabase `traffic_events` (dibuat lewat SQL Editor).
 * Bila tabel belum ada / Supabase tak terkonfigurasi: diam-diam no-op,
 * TIDAK PERNAH mengganggu pengalaman pengunjung.
 */
class TrafficController extends Controller
{
    public function hit(Request $request): Response
    {
        try {
            $path = (string) ($request->json('path') ?? $request->input('path') ?? '');
            // Validasi ketat: hanya path internal wajar yang dicatat.
            $path = strtok($path, '?#') ?: '';
            if ($path === '' || $path[0] !== '/' || strlen($path) > 200) {
                return response('', 204);
            }
            if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
                return response('', 204);
            }

            $url = config('services.supabase.url');
            $key = config('services.supabase.service_key');
            if ($url && $key) {
                Http::withHeaders([
                    'apikey' => $key,
                    'Authorization' => "Bearer {$key}",
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=minimal',
                ])->timeout(5)->post("{$url}/rest/v1/traffic_events", [
                    'path' => $path,
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Traffic beacon gagal (diabaikan): ' . $e->getMessage());
        }

        return response('', 204);
    }
}
