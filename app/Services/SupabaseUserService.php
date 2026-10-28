<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseUserService
{
    private ?string $url;
    private ?string $key;

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

    public function enabled(): bool
    {
        return !empty($this->url) && !empty($this->key);
    }

    /**
     * ID user yang STABIL untuk tabel bersama di Supabase (bookmarks,
     * majelis_follows, push_subscriptions, attendances): ID baris
     * app_users yang dicocokkan via EMAIL user yang sedang login.
     *
     * JANGAN pakai Auth::id() (ID SQLite lokal) untuk tabel Supabase:
     * SQLite di Vercel ephemeral — instance dingin mewarisi file yang
     * ter-commit dan menulis ID baru, sehingga user yang sama mendapat
     * ID berbeda-beda antar instance (bookmark/follow/subscription
     * tercerai ke banyak ID hantu: 195/216/241 untuk satu orang).
     * Email adalah satu-satunya kunci yang stabil.
     */
    public function currentAppUserId(): ?int
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || empty($user->email)) {
            return null;
        }
        $row = $this->findOrCreate(['name' => $user->name, 'email' => $user->email]);
        return isset($row['id']) ? (int) $row['id'] : null;
    }

    public function getSupabaseUrl(): ?string
    {
        return $this->url;
    }

    public function getServiceKey(): ?string
    {
        return $this->key;
    }

    private function headers(): array
    {
        return [
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Cari user by email, atau buat baru jika belum ada.
     * Return array user atau null.
     */
    public function findOrCreate(array $data): ?array
    {
        if (!$this->enabled()) return null;

        try {
            // Cari by email
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/app_users", [
                    'email' => 'eq.' . $data['email'],
                    'select' => '*',
                ]);

            $rows = $res->json();
            if (!empty($rows)) {
                return $rows[0];
            }

            // Buat baru via upsert (merge-duplicates di kolom email) +
            // return=representation: menutup celah balapan check-then-insert
            // dan menghilangkan GET kedua (audit 2026-10-21, M3). Keamanan
            // penuhnya ditopang unique index email di SQL pendamping.
            $res = Http::withHeaders(array_merge($this->headers(), [
                    'Prefer' => 'resolution=merge-duplicates, return=representation',
                ]))
                ->timeout(10)
                ->post("{$this->url}/rest/v1/app_users", [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'] ?? null,
                    'provider' => $data['provider'] ?? null,
                    'provider_id' => $data['provider_id'] ?? null,
                    'email_verified_at' => now()->toIso8601String(),
                ]);

            if ($res->successful()) {
                $rows = $res->json();
                return $rows[0] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Supabase findOrCreate gagal: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Update user by ID.
     * PENTING: filter ID harus di query string URL — Laravel Http::patch()
     * hanya menerima 2 parameter, argumen ke-3 akan diam-diam dibuang
     * sehingga PATCH tanpa filter akan menimpa SELURUH baris tabel.
     */
    public function update(int $id, array $data): bool
    {
        if (!$this->enabled()) return false;

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->patch("{$this->url}/rest/v1/app_users?id=eq.{$id}", array_merge($data, [
                    'updated_at' => now()->toIso8601String(),
                ]));

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase user update gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update password user by email di Supabase.
     */
    public function updatePassword(string $email, string $newPassword): bool
    {
        if (!$this->enabled()) return false;

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->patch("{$this->url}/rest/v1/app_users?email=eq." . urlencode($email), [
                    'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
                    'updated_at' => now()->toIso8601String(),
                ]);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase user updatePassword gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Catat aktivitas user (perintah user 2026-10-23): kolom last_login_at /
     * last_seen_at di app_users, agar admin bisa melihat kapan user terakhir
     * login & berkunjung. Fire-and-forget: gagal (termasuk kolom belum dibuat
     * lewat SQL Editor) TIDAK PERNAH mengganggu alur login/kunjungan.
     * $fields hanya boleh berisi last_login_at dan/atau last_seen_at.
     */
    public function touchActivity(string $email, array $fields): void
    {
        if (!$this->enabled()) return;

        $allowed = array_intersect_key($fields, array_flip(['last_login_at', 'last_seen_at']));
        if ($allowed === []) return;

        try {
            Http::withHeaders(array_merge($this->headers(), [
                    'Prefer' => 'return=minimal',
                ]))
                ->timeout(5)
                ->patch("{$this->url}/rest/v1/app_users?email=eq." . urlencode(strtolower(trim($email))), $allowed);
        } catch (\Exception $e) {
            Log::debug('Supabase touchActivity diabaikan: ' . $e->getMessage());
        }
    }

    /**
     * Ambil user by ID.
     */
    public function find(int $id): ?array
    {
        if (!$this->enabled()) return null;

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/app_users", [
                    'id' => 'eq.' . $id,
                    'select' => '*',
                ]);

            $rows = $res->json();
            return $rows[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Supabase find user gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil user by Email (case-insensitive).
     */
    public function findByEmail(string $email): ?array
    {
        if (!$this->enabled()) return null;

        try {
            $cleanEmail = strtolower(trim($email));
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/app_users", [
                    'email' => 'eq.' . $cleanEmail,
                    'select' => '*',
                ]);

            $rows = $res->json();
            return $rows[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Supabase findByEmail gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simpan atau update push subscription (upsert via Prefer header).
     */
    public function upsertPushSubscription(array $data): bool
    {
        if (!$this->enabled()) return false;

        try {
            // Upsert via POST dengan Prefer: resolution=merge-duplicates
            $res = Http::withHeaders(array_merge($this->headers(), ['Prefer' => 'resolution=merge-duplicates']))
                ->timeout(10)
                ->post("{$this->url}/rest/v1/push_subscriptions", $data);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase upsertPushSubscription gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus push subscription by endpoint.
     */
    public function deletePushSubscription(string $endpoint): bool
    {
        if (!$this->enabled()) return false;

        try {
            // PENTING: filter WAJIB di query string URL. Filter yang dikirim
            // sebagai body DELETE diabaikan PostgREST → DELETE tanpa filter
            // akan menghapus SELURUH tabel (bug C2, audit 2026-10-21).
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->delete("{$this->url}/rest/v1/push_subscriptions?endpoint=eq." . urlencode($endpoint));

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase deletePushSubscription gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil push subscriptions dengan pagination eksplisit.
     * PostgREST membatasi diam-diam di 1000 baris — limit/offset harus
     * dinyatakan agar broadcast besar tidak kehilangan ekor daftarnya.
     */
    public function getPushSubscriptions(int $limit = 1000, int $offset = 0): array
    {
        if (!$this->enabled()) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/push_subscriptions", [
                    'select' => 'endpoint,p256dh,auth,user_id',
                    'order' => 'id.asc',
                    'limit' => $limit,
                    'offset' => $offset,
                ]);

            return $res->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('Supabase getPushSubscriptions gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Token reset sandi di Supabase (audit 2026-10-21, M1).
     * Tabel lokal SQLite per-instance membuat tautan reset sering
     * "tidak valid" secara acak antar instance Vercel; token kini
     * disimpan terpusat. Butuh tabel password_reset_tokens di Supabase
     * (SQL pendamping); bila tabel belum ada, semua method ini gagal
     * senyap dan alur lokal lama tetap berjalan sebagai fallback.
     */
    public function putResetToken(string $email, string $hashedToken): void
    {
        if (!$this->enabled()) return;
        try {
            Http::withHeaders(array_merge($this->headers(), [
                    'Prefer' => 'resolution=merge-duplicates',
                ]))
                ->timeout(10)
                ->post("{$this->url}/rest/v1/password_reset_tokens", [
                    'email' => strtolower(trim($email)),
                    'token' => $hashedToken,
                    'created_at' => now()->toIso8601String(),
                ]);
        } catch (\Exception $e) {
            Log::warning('Supabase putResetToken gagal: ' . $e->getMessage());
        }
    }

    public function getResetToken(string $email): ?array
    {
        if (!$this->enabled()) return null;
        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/password_reset_tokens", [
                    'email' => 'eq.' . strtolower(trim($email)),
                    'select' => 'token,created_at',
                ]);
            if (!$res->successful()) return null;
            $rows = $res->json();
            return $rows[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Supabase getResetToken gagal: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteResetToken(string $email): void
    {
        if (!$this->enabled()) return;
        try {
            Http::withHeaders($this->headers())
                ->timeout(10)
                ->delete("{$this->url}/rest/v1/password_reset_tokens?email=eq." . urlencode(strtolower(trim($email))));
        } catch (\Exception $e) {
            Log::warning('Supabase deleteResetToken gagal: ' . $e->getMessage());
        }
    }

    /**
     * Ambil daftar user_id yang mem-bookmark kajian tertentu.
     */
    public function getBookmarkUserIds(int $kajianId): array
    {
        if (!$this->enabled()) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/bookmarks", [
                    'select' => 'user_id',
                    'kajian_id' => 'eq.' . $kajianId,
                ]);

            $rows = $res->json() ?? [];
            return array_values(array_unique(array_map(fn ($r) => (int) ($r['user_id'] ?? 0), $rows)));
        } catch (\Exception $e) {
            Log::warning('Supabase getBookmarkUserIds gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil daftar user_id yang mengikuti majelis (masjid) tertentu.
     */
    public function getMajelisFollowerUserIds(string $masjid): array
    {
        if (!$this->enabled()) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/majelis_follows", [
                    'select' => 'user_id',
                    'masjid' => 'eq.' . $masjid,
                ]);

            $rows = $res->json() ?? [];
            return array_values(array_unique(array_map(fn ($r) => (int) ($r['user_id'] ?? 0), $rows)));
        } catch (\Exception $e) {
            Log::warning('Supabase getMajelisFollowerUserIds gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil daftar user_id yang mengikuti ustadz tertentu.
     * (Segmen No.5 — follow ustadz, 2026-10-28.)
     */
    public function getUstadzFollowerUserIds(string $ustadz): array
    {
        if (!$this->enabled()) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/ustadz_follows", [
                    'select' => 'user_id',
                    'ustadz' => 'eq.' . $ustadz,
                ]);

            $rows = $res->json() ?? [];
            return array_values(array_unique(array_map(fn ($r) => (int) ($r['user_id'] ?? 0), $rows)));
        } catch (\Exception $e) {
            Log::warning('Supabase getUstadzFollowerUserIds gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Simpan follow ustadz ke Supabase (upsert; abaikan duplikat).
     * Bila tabel belum ada (SQL pendamping belum dijalankan user),
     * gagal senyap — fallback lokal SQLite tetap berjalan.
     */
    public function upsertUstadzFollow(array $data): bool
    {
        if (!$this->enabled()) return false;

        try {
            $res = Http::withHeaders(array_merge($this->headers(), ['Prefer' => 'resolution=ignore-duplicates']))
                ->timeout(10)
                ->post("{$this->url}/rest/v1/ustadz_follows", $data);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase upsertUstadzFollow gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus follow ustadz di Supabase. Filter WAJIB di query string
     * (body DELETE diabaikan PostgREST — bug C2, audit 2026-10-21).
     */
    public function deleteUstadzFollow(int $userId, string $ustadz): bool
    {
        if (!$this->enabled()) return false;

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->delete("{$this->url}/rest/v1/ustadz_follows?user_id=eq.{$userId}&ustadz=eq." . urlencode($ustadz));

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase deleteUstadzFollow gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Preferensi notifikasi ibadah per user (Segmen No.2 — pengingat
     * adzan/dzikir/puasa, 2026-10-28). Upsert by user_id.
     */
    public function upsertNotificationPrefs(array $data): bool
    {
        if (!$this->enabled()) return false;

        try {
            $res = Http::withHeaders(array_merge($this->headers(), ['Prefer' => 'resolution=merge-duplicates']))
                ->timeout(10)
                ->post("{$this->url}/rest/v1/notification_prefs", $data);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase upsertNotificationPrefs gagal: ' . $e->getMessage());
            return false;
        }
    }

    public function getNotificationPrefs(int $userId): ?array
    {
        if (!$this->enabled()) return null;

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("{$this->url}/rest/v1/notification_prefs", [
                    'select' => 'user_id,adzan,dzikir,puasa,city,lat,lon',
                    'user_id' => 'eq.' . $userId,
                    'limit' => 1,
                ]);

            $rows = $res->json() ?? [];
            return $rows[0] ?? null;
        } catch (\Exception $e) {
            Log::warning('Supabase getNotificationPrefs gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Semua preferensi ibadah yang opt-in untuk satu topik
     * (adzan|dzikir|puasa) — dipakai cron pengingat + filter push.
     */
    public function getOptInPrefs(string $topic): array
    {
        if (!$this->enabled()) return [];
        if (!in_array($topic, ['adzan', 'dzikir', 'puasa'], true)) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/notification_prefs", [
                    'select' => 'user_id,adzan,dzikir,puasa,city,lat,lon',
                    $topic => 'is.true',
                ]);

            return $res->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('Supabase getOptInPrefs gagal: ' . $e->getMessage());
            return [];
        }
    }

    /** Daftar user_id yang opt-in satu topik pengingat ibadah. */
    public function getOptInUserIds(string $topic): array
    {
        $rows = $this->getOptInPrefs($topic);
        return array_values(array_unique(array_map(fn ($r) => (int) ($r['user_id'] ?? 0), $rows)));
    }
}
