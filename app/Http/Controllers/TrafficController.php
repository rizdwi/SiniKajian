<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Beacon pencatat pageview first-party (tanpa pihak ketiga, tanpa cookie
 * pelacak, hanya path halaman). Dipakai menu admin Traffic.
 *
 * Dua jalur:
 * - hit()      : tamu/anonim lewat grup api (stateless) — hanya path.
 * - hitUser()  : user login lewat grup web (sesi) — path + email user dari
 *   SESI server (tidak pernah dari payload klien), sekaligus memperbarui
 *   last_seen_at di app_users (di-throttle 5 menit). Perintah user
 *   2026-10-23: admin ingin tahu aktivitas kunjungan para user.
 *
 * Menulis ke tabel Supabase `traffic_events` (dibuat lewat SQL Editor).
 * Bila tabel/kolom belum ada / Supabase tak terkonfigurasi: diam-diam
 * no-op, TIDAK PERNAH mengganggu pengalaman pengunjung.
 */
class TrafficController extends Controller
{
    public function hit(Request $request): Response
    {
        $path = $this->cleanPath($request);
        if ($path !== null) {
            $this->storeEvent(['path' => $path]);
        }

        return response('', 204);
    }

    public function hitUser(Request $request): Response
    {
        try {
            $user = Auth::user();
            $path = $this->cleanPath($request);
            if ($user && !empty($user->email) && $path !== null) {
                $email = strtolower((string) $user->email);
                $this->storeEvent(['path' => $path, 'user_email' => $email]);

                // last_seen_at maksimal 1x per 5 menit per user.
                $cacheKey = 'last-seen:' . md5($email);
                if (Cache::add($cacheKey, 1, 300)) {
                    (new \App\Services\SupabaseUserService())->touchActivity($email, [
                        'last_seen_at' => now()->toIso8601String(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Traffic beacon user gagal (diabaikan): ' . $e->getMessage());
        }

        return response('', 204);
    }

    /** Path internal yang valid untuk dicatat, atau null bila ditolak. */
    private function cleanPath(Request $request): ?string
    {
        $path = (string) ($request->json('path') ?? $request->input('path') ?? '');
        // Validasi ketat: hanya path internal wajar yang dicatat.
        $path = strtok($path, '?#') ?: '';
        if ($path === '' || $path[0] !== '/' || strlen($path) > 200) {
            return null;
        }
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
            return null;
        }

        return $path;
    }

    private function storeEvent(array $fields): void
    {
        try {
            $url = config('services.supabase.url');
            $key = config('services.supabase.service_key');
            if ($url && $key) {
                Http::withHeaders([
                    'apikey' => $key,
                    'Authorization' => "Bearer {$key}",
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=minimal',
                ])->timeout(5)->post("{$url}/rest/v1/traffic_events", $fields);
            }
        } catch (\Throwable $e) {
            Log::debug('Traffic beacon gagal (diabaikan): ' . $e->getMessage());
        }
    }
}
