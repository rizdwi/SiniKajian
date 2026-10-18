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
     */
    public function index(): View
    {
        $this->ensureAdmin();

        $pendingKajians = Kajian::where('verified', false)
            ->orderBy('id', 'desc')
            ->get();

        $verifiedKajians = Kajian::where('verified', true)
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.kajian', compact('pendingKajians', 'verifiedKajians'));
    }

    /**
     * Setujui dan terbitkan kajian ke publik.
     */
    public function verify(Kajian $kajian): RedirectResponse
    {
        $this->ensureAdmin();

        $kajian->verified = true;
        $kajian->status = 'aktif';
        $kajian->save();

        // Sinkronisasi status terverifikasi ke Supabase jika aktif
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_role_key') ?: config('services.supabase.key');
        if ($supabaseUrl && $supabaseKey) {
            try {
                Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                    'Content-Type' => 'application/json',
                ])->patch("{$supabaseUrl}/rest/v1/kajian?id=eq.{$kajian->id}", [
                    'verified' => true,
                    'status' => 'aktif',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Supabase verify sync error: ' . $e->getMessage());
            }
        }

        return back()->with('success', "Kajian '{$kajian->title}' berhasil diverifikasi dan aktif di feed publik.");
    }

    /**
     * Hapus kajian yang tidak valid / spam.
     */
    public function destroy(Kajian $kajian): RedirectResponse
    {
        $this->ensureAdmin();

        $title = $kajian->title;
        $id = $kajian->id;
        $kajian->delete();

        // Hapus dari Supabase jika aktif
        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_role_key') ?: config('services.supabase.key');
        if ($supabaseUrl && $supabaseKey) {
            try {
                Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                ])->delete("{$supabaseUrl}/rest/v1/kajian?id=eq.{$id}");
            } catch (\Throwable $e) {
                Log::warning('Supabase delete sync error: ' . $e->getMessage());
            }
        }

        return back()->with('success', "Kajian '{$title}' telah dihapus.");
    }
}
