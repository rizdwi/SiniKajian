<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Menu admin "Akun Pantau": kelola akun sumber scraping kajian
 * (monitor + blacklist) yang tersimpan di tabel Supabase
 * `monitor_accounts` — satu-satunya sumber kebenaran daftar akun;
 * cron scraper membaca dari tabel ini (perintah user 2026-10-21).
 */
class AdminAccountController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Akses khusus administrator Majelisku.');
        }
    }

    private function sb(string $method, string $path, ?array $body = null, array $extraHeaders = []): ?array
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_key');
        if (!$url || !$key) return null;
        try {
            $req = Http::withHeaders(array_merge([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
                'Content-Type' => 'application/json',
            ], $extraHeaders))->timeout(10);
            $res = match ($method) {
                'GET' => $req->get("{$url}/rest/v1/{$path}"),
                'POST' => $req->post("{$url}/rest/v1/{$path}", $body ?? []),
                'PATCH' => $req->patch("{$url}/rest/v1/{$path}", $body ?? []),
                'DELETE' => $req->delete("{$url}/rest/v1/{$path}"),
                default => null,
            };
            if ($res === null || !$res->successful()) {
                if ($res !== null) Log::warning("AdminAccount sb {$method} {$path}: HTTP " . $res->status());
                return null;
            }
            $json = $res->json();
            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::warning("AdminAccount sb {$method} {$path} gagal: " . $e->getMessage());
            return null;
        }
    }

    /** Daftar semua akun (monitor + blacklist). */
    public function index(): View
    {
        $this->ensureAdmin();

        $rows = $this->sb('GET', 'monitor_accounts?select=*&order=list_type.asc,platform.asc,username.asc&limit=1000');
        $tableReady = $rows !== null;
        $rows = $rows ?? [];

        return view('admin.akun', [
            'tableReady' => $tableReady,
            'monitors' => array_values(array_filter($rows, fn ($r) => ($r['list_type'] ?? 'monitor') === 'monitor')),
            'blacklists' => array_values(array_filter($rows, fn ($r) => ($r['list_type'] ?? '') === 'blacklist')),
        ]);
    }

    /** Tambah akun baru. */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'list_type' => 'required|in:monitor,blacklist',
            'platform' => 'nullable|in:instagram,facebook,threads',
            'username' => 'required|string|max:120',
            'url' => 'nullable|string|max:300',
            'region' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:300',
        ]);
        $data['username'] = ltrim(trim($data['username']), '@');
        if (empty($data['url']) && !empty($data['platform']) && $data['list_type'] === 'monitor') {
            $data['url'] = match ($data['platform']) {
                'instagram' => 'https://www.instagram.com/' . $data['username'],
                'facebook' => 'https://www.facebook.com/' . $data['username'],
                'threads' => 'https://www.threads.net/@' . $data['username'],
                default => null,
            };
        }

        $res = $this->sb('POST', 'monitor_accounts', $data, ['Prefer' => 'return=representation']);
        if ($res === null) {
            return back()->with('error', 'Gagal menyimpan — tabel monitor_accounts belum dibuat atau Supabase tak terjangkau.');
        }
        return back()->with('success', "Akun '{$data['username']}' ditambahkan ke daftar " . ($data['list_type'] === 'monitor' ? 'pantau' : 'blacklist') . '.');
    }

    /** Form ubah akun. */
    public function edit(int $id): View
    {
        $this->ensureAdmin();
        $rows = $this->sb('GET', "monitor_accounts?select=*&id=eq.{$id}");
        if (!$rows) abort(404, 'Akun tidak ditemukan.');
        return view('admin.akun-edit', ['account' => $rows[0]]);
    }

    /** Simpan perubahan akun. */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'platform' => 'nullable|in:instagram,facebook,threads',
            'username' => 'required|string|max:120',
            'url' => 'nullable|string|max:300',
            'region' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:300',
        ]);
        $data['username'] = ltrim(trim($data['username']), '@');

        $res = $this->sb('PATCH', "monitor_accounts?id=eq.{$id}", $data, ['Prefer' => 'return=representation']);
        if ($res === null || $res === []) {
            return back()->with('error', 'Gagal mengubah akun.');
        }
        return redirect()->route('admin.akun.index')->with('success', "Akun '{$data['username']}' diperbarui.");
    }

    /** Aktif/nonaktifkan akun (nonaktif = dilewati scraper, tidak dihapus). */
    public function toggle(int $id): RedirectResponse
    {
        $this->ensureAdmin();
        $rows = $this->sb('GET', "monitor_accounts?select=id,is_active,username&id=eq.{$id}");
        if (!$rows) return back()->with('error', 'Akun tidak ditemukan.');
        $now = !($rows[0]['is_active'] ?? true);
        $this->sb('PATCH', "monitor_accounts?id=eq.{$id}", ['is_active' => $now], ['Prefer' => 'return=minimal']);
        return back()->with('success', "Akun '{$rows[0]['username']}' " . ($now ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    /** Hapus akun permanen dari daftar. */
    public function destroy(int $id): RedirectResponse
    {
        $this->ensureAdmin();
        $rows = $this->sb('GET', "monitor_accounts?select=id,username&id=eq.{$id}");
        $name = $rows[0]['username'] ?? "#{$id}";
        $res = $this->sb('DELETE', "monitor_accounts?id=eq.{$id}", null, ['Prefer' => 'return=minimal']);
        if ($res === null) return back()->with('error', 'Gagal menghapus akun.');
        return back()->with('success', "Akun '{$name}' dihapus dari daftar.");
    }
}
