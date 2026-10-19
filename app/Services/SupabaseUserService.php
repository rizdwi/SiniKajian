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

            // Buat baru
            $res = Http::withHeaders($this->headers())
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
                // Ambil yang baru dibuat
                $res = Http::withHeaders($this->headers())
                    ->timeout(10)
                    ->get("{$this->url}/rest/v1/app_users", [
                        'email' => 'eq.' . $data['email'],
                        'select' => '*',
                    ]);
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
                    'email' => 'ilike.' . $cleanEmail,
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
            $res = Http::withHeaders($this->headers())
                ->timeout(10)
                ->delete("{$this->url}/rest/v1/push_subscriptions", [
                    'endpoint' => 'eq.' . $endpoint,
                ]);

            return $res->successful();
        } catch (\Exception $e) {
            Log::warning('Supabase deletePushSubscription gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua push subscriptions.
     */
    public function getPushSubscriptions(): array
    {
        if (!$this->enabled()) return [];

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->url}/rest/v1/push_subscriptions", [
                    'select' => 'endpoint,p256dh,auth',
                ]);

            return $res->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('Supabase getPushSubscriptions gagal: ' . $e->getMessage());
            return [];
        }
    }
}
