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
}
