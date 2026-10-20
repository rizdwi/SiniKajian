<?php

namespace App\Cache;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cache store bersama berbasis tabel Supabase `app_cache` (PostgREST).
 *
 * Audit 2026-10-21 (H3): CACHE_STORE=array di Vercel berarti cache &
 * rate-limiter hanya hidup di memori satu instance serverless — throttle
 * login/register/reset praktis tidak berfungsi dan cache kajian 5 menit
 * hampir tidak pernah kena. Store ini memindahkan state itu ke Supabase
 * (satu-satunya store bersama yang tersedia tanpa kredensial baru).
 *
 * Degradasi aman: bila tabel belum dibuat (SQL pendamping belum
 * dijalankan) atau Supabase tidak terjangkau, semua operasi gagal senyap
 * (get=null, put=false) dan ditandai sekali per request agar tidak
 * membayar roundtrip berulang — aplikasi tetap berjalan.
 *
 * Butuh SQL (SQL Editor, oleh user):
 *   CREATE TABLE IF NOT EXISTS app_cache (
 *     key TEXT PRIMARY KEY, value TEXT, expiration BIGINT
 *   );
 */
class SupabaseStore implements Store
{
    private ?bool $available = null;

    private function base(): ?string
    {
        $url = config('services.supabase.url');
        return $url ? rtrim($url, '/') . '/rest/v1/app_cache' : null;
    }

    private function key(): ?string
    {
        return config('services.supabase.service_key') ?: config('services.supabase.key');
    }

    private function request()
    {
        $key = $this->key();
        if (!$key || !$this->base()) {
            return null;
        }
        return Http::withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->timeout(5);
    }

    public function get($key)
    {
        if ($this->available === false) {
            return null;
        }
        $row = $this->fetchRow($key);
        if (!$row) return null;
        if ((int) $row['expiration'] < time()) {
            $this->forget($key);
            return null;
        }
        $value = @unserialize((string) $row['value']);
        return $value === false && $row['value'] !== serialize(false) ? null : $value;
    }

    /**
     * Ambil baris mentah (value terse­rialisasi + expiration) atau null.
     * Mengelola flag ketersediaan agar kegagalan tidak dibayar berulang.
     */
    private function fetchRow($key): ?array
    {
        if ($this->available === false) {
            return null;
        }
        try {
            $req = $this->request();
            if (!$req) return null;
            $res = $req->get($this->base(), [
                'key' => 'eq.' . $key,
                'select' => 'value,expiration',
            ]);
            if (!$res->successful()) {
                $this->available = false;
                return null;
            }
            $this->available = true;
            return $res->json()[0] ?? null;
        } catch (\Throwable $e) {
            $this->available = false;
            Log::warning('SupabaseStore fetchRow gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Perpanjang masa hidup kunci yang sudah ada (kontrak Store Laravel 12).
     */
    public function touch($key, $seconds)
    {
        $row = $this->fetchRow($key);
        if (!$row) return false;
        if ((int) $row['expiration'] < time()) return false;
        try {
            $req = $this->request();
            if (!$req) return false;
            $res = $req->withHeaders(['Prefer' => 'return=minimal'])
                ->patch($this->base() . '?key=eq.' . urlencode((string) $key), [
                    'expiration' => time() + (int) $seconds,
                ]);
            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('SupabaseStore touch gagal: ' . $e->getMessage());
            return false;
        }
    }

    public function many(array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function put($key, $value, $seconds)
    {
        if ($this->available === false) {
            return false;
        }
        try {
            $req = $this->request();
            if (!$req) return false;
            $res = $req->withHeaders(['Prefer' => 'resolution=merge-duplicates, return=minimal'])
                ->post($this->base(), [
                    'key' => $key,
                    'value' => serialize($value),
                    'expiration' => time() + (int) $seconds,
                ]);
            $this->available = $res->successful();
            return $this->available;
        } catch (\Throwable $e) {
            $this->available = false;
            Log::warning('SupabaseStore put gagal: ' . $e->getMessage());
            return false;
        }
    }

    public function putMany(array $values, $seconds)
    {
        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->put($key, $value, $seconds) && $ok;
        }
        return $ok;
    }

    public function increment($key, $value = 1)
    {
        $current = (int) ($this->get($key) ?? 0);
        $new = $current + $value;
        // Pertahankan TTL kasar 1 hari untuk counter (rate limiter menimpa
        // dengan TTL-nya sendiri lewat put pada hit pertama).
        $this->put($key, $new, 86400);
        return $new;
    }

    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    public function forever($key, $value)
    {
        return $this->put($key, $value, 86400 * 365);
    }

    public function forget($key)
    {
        try {
            $req = $this->request();
            if (!$req) return false;
            $res = $req->delete($this->base() . '?key=eq.' . urlencode((string) $key));
            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('SupabaseStore forget gagal: ' . $e->getMessage());
            return false;
        }
    }

    public function flush()
    {
        try {
            $req = $this->request();
            if (!$req) return false;
            // Filter wajib untuk DELETE PostgREST; like.* cocok untuk
            // semua key non-kosong di tabel khusus ini.
            $res = $req->delete($this->base() . '?key=like.*');
            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('SupabaseStore flush gagal: ' . $e->getMessage());
            return false;
        }
    }

    public function getPrefix()
    {
        return '';
    }
}
