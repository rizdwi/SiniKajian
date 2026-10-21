<?php

namespace App\Http\Controllers;

use App\Models\Kajian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Pastikan hanya akun admin yang berhak mengakses dashboard moderasi.
     */
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Akses khusus administrator Majelisku.');
        }
    }

    /**
     * Tampilkan antarmuka moderasi kajian masuk dan daftar seluruh kajian.
     * Baca dari Supabase (SQLite lokal kosong di Vercel).
     */
    public function index(): View
    {
        $this->ensureAdmin();

        $sbKajianSvc = new \App\Services\SupabaseKajianService();
        $pendingKajians = collect();
        $verifiedKajians = collect();

        if ($sbKajianSvc->isConfigured()) {
            try {
                $rows = $sbKajianSvc->getAllKajians(); // termasuk yang belum verified
                $all = \App\Services\SupabaseKajianService::toCollection($rows);
                $pendingKajians = $all->where('verified', false)->sortByDesc('id')->values();
                $verifiedKajians = $all->where('verified', true)->sortByDesc('id')->values();
            } catch (\Throwable $e) {
                Log::warning('Admin panel Supabase read gagal: ' . $e->getMessage());
            }
        } else {
            // Fallback SQLite lokal (dev)
            $pendingKajians = Kajian::where('verified', false)->orderBy('id', 'desc')->get();
            $verifiedKajians = Kajian::where('verified', true)->orderBy('id', 'desc')->get();
        }

        return view('admin.kajian', compact('pendingKajians', 'verifiedKajians'));
    }

    /**
     * Setujui dan terbitkan kajian ke publik.
     * Beroperasi LANGSUNG ke Supabase by id mentah (audit 2026-10-21, H6):
     * route-model binding ke SQLite lokal per-instance membuat moderasi
     * 404/mutasi baris basi secara acak antar instance Vercel.
     */
    public function verify($kajian): RedirectResponse
    {
        $this->ensureAdmin();
        $id = (int) $kajian;

        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');
        if ($supabaseUrl && $supabaseKey) {
            try {
                $res = Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation',
                ])->timeout(10)->patch("{$supabaseUrl}/rest/v1/kajian?id=eq.{$id}", [
                    'verified' => true,
                    'status' => 'aktif',
                ]);
                if (!$res->successful() || empty($res->json())) {
                    return back()->with('error', 'Kajian tidak ditemukan di database utama.');
                }
                $title = $res->json()[0]['title'] ?? "#{$id}";
            } catch (\Throwable $e) {
                Log::warning('Supabase verify error: ' . $e->getMessage());
                return back()->with('error', 'Gagal menghubungi database utama, coba lagi.');
            }
            return back()->with('success', "Kajian '{$title}' berhasil diverifikasi dan aktif di feed publik.");
        }

        // Fallback SQLite lokal (dev saja)
        $local = Kajian::find($id);
        if (!$local) {
            return back()->with('error', 'Kajian tidak ditemukan.');
        }
        $local->verified = true;
        $local->status = 'aktif';
        $local->save();
        return back()->with('success', "Kajian '{$local->title}' berhasil diverifikasi dan aktif di feed publik.");
    }

    /**
     * Hapus kajian yang tidak valid / spam — langsung dari Supabase.
     */
    public function destroy($kajian): RedirectResponse
    {
        $this->ensureAdmin();
        $id = (int) $kajian;

        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');
        if ($supabaseUrl && $supabaseKey) {
            try {
                $res = Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                    'Prefer' => 'return=representation',
                ])->timeout(10)->delete("{$supabaseUrl}/rest/v1/kajian?id=eq.{$id}");
                if (!$res->successful() || empty($res->json())) {
                    return back()->with('error', 'Kajian tidak ditemukan di database utama.');
                }
                $title = $res->json()[0]['title'] ?? "#{$id}";
            } catch (\Throwable $e) {
                Log::warning('Supabase delete error: ' . $e->getMessage());
                return back()->with('error', 'Gagal menghubungi database utama, coba lagi.');
            }
            return back()->with('success', "Kajian '{$title}' telah dihapus.");
        }

        // Fallback SQLite lokal (dev saja)
        $local = Kajian::find($id);
        if (!$local) {
            return back()->with('error', 'Kajian tidak ditemukan.');
        }
        $title = $local->title;
        $local->delete();
        return back()->with('success', "Kajian '{$title}' telah dihapus.");
    }

    /** Hitung baris tabel Supabase via PostgREST count=exact (null bila gagal). */
    private function sbCount(string $table, array $filters = []): ?int
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_key');
        if (!$url || !$key) return null;
        try {
            $res = Http::withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
                'Prefer' => 'count=exact',
            ])->timeout(10)->get("{$url}/rest/v1/{$table}", array_merge($filters, [
                'select' => 'id',
                'limit' => 0,
            ]));
            if (!$res->successful()) return null;
            $range = $res->header('Content-Range');
            if ($range && str_contains($range, '/')) {
                return (int) substr($range, strrpos($range, '/') + 1);
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning("sbCount {$table} gagal: " . $e->getMessage());
            return null;
        }
    }

    /** Ambil baris tabel Supabase (null bila gagal/tabel belum ada). */
    private function sbGet(string $table, array $params): ?array
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_key');
        if (!$url || !$key) return null;
        try {
            $res = Http::withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
            ])->timeout(10)->get("{$url}/rest/v1/{$table}", $params);
            if (!$res->successful()) return null;
            $json = $res->json();
            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::warning("sbGet {$table} gagal: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Menu admin: pantau traffic & aktivitas pengguna (perintah user
     * 2026-10-21). Semua angka agregat dari Supabase; pageview berasal
     * dari beacon first-party (tabel traffic_events).
     */
    public function traffic(): View
    {
        $this->ensureAdmin();

        $now = now('Asia/Jakarta');
        $today = $now->toDateString();
        $since7 = $now->copy()->subDays(6)->toDateString();
        $since14 = $now->copy()->subDays(13)->startOfDay()->toIso8601String();
        $since30 = $now->copy()->subDays(29)->toDateString();

        // --- Pengguna & keterlibatan (tabel yang sudah ada) ---
        $stats = [
            'users_total' => $this->sbCount('app_users'),
            'users_today' => $this->sbCount('app_users', ['created_at' => 'gte.' . $today]),
            'users_7d' => $this->sbCount('app_users', ['created_at' => 'gte.' . $since7]),
            'users_30d' => $this->sbCount('app_users', ['created_at' => 'gte.' . $since30]),
            'push_subs' => $this->sbCount('push_subscriptions'),
            'bookmarks' => $this->sbCount('bookmarks'),
            'attendances' => $this->sbCount('attendances'),
            'follows' => $this->sbCount('majelis_follows'),
            'kajian_cancelled' => $this->sbCount('kajian', ['is_cancelled' => 'is.true']),
        ];

        $recentUsers = $this->sbGet('app_users', [
            'select' => 'id,name,email,provider,created_at',
            'order' => 'id.desc',
            'limit' => 8,
        ]) ?? [];

        // --- Kajian aktif + masjid tersering ---
        $svc = new \App\Services\SupabaseKajianService();
        $active = $svc->isConfigured() ? $svc->getActiveKajians() : [];
        $stats['kajian_active'] = count($active);
        $byMasjid = [];
        foreach ($active as $row) {
            $m = trim((string) ($row['masjid'] ?? ''));
            if ($m !== '') $byMasjid[$m] = ($byMasjid[$m] ?? 0) + 1;
        }
        arsort($byMasjid);
        $topMasjid = array_slice($byMasjid, 0, 8, true);

        // --- Pageview (tabel traffic_events; null = tabel belum dibuat) ---
        $events = $this->sbGet('traffic_events', [
            'select' => 'path,created_at',
            'created_at' => 'gte.' . $since14,
            'order' => 'created_at.desc',
            'limit' => 10000,
        ]);
        $traffic = null;
        if ($events !== null) {
            $perDay = [];
            for ($i = 13; $i >= 0; $i--) {
                $perDay[$now->copy()->subDays($i)->toDateString()] = 0;
            }
            $topPages = [];
            $pvToday = 0;
            $pv7d = 0;
            foreach ($events as $ev) {
                $day = \Carbon\Carbon::parse($ev['created_at'])->setTimezone('Asia/Jakarta')->toDateString();
                if (isset($perDay[$day])) $perDay[$day]++;
                if ($day === $today) $pvToday++;
                if ($day >= $since7) {
                    $pv7d++;
                    $p = (string) ($ev['path'] ?? '/');
                    $topPages[$p] = ($topPages[$p] ?? 0) + 1;
                }
            }
            arsort($topPages);
            $traffic = [
                'today' => $pvToday,
                '7d' => $pv7d,
                '30d' => $this->sbCount('traffic_events', ['created_at' => 'gte.' . $since30]),
                'per_day' => $perDay,
                'top_pages' => array_slice($topPages, 0, 10, true),
            ];
        }

        return view('admin.traffic', compact('stats', 'recentUsers', 'topMasjid', 'traffic'));
    }
}
