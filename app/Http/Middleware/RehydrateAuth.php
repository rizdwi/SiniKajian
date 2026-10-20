<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rehidrasi login lintas instance Vercel.
 *
 * AKAR MASALAH (audit bookmark 2026-10-20): setiap instance Vercel
 * menyalin database/database.sqlite yang ter-commit ke /tmp SATU KALI
 * (api/index.php) lalu menulis ke salinannya sendiri. Setelah file
 * ter-commit dikosongkan dari user tes, instance dingin TIDAK punya
 * baris user sama sekali — sesi cookie memang membawa ID user, tetapi
 * Auth::check() mencarinya di SQLite instance itu dan tidak menemukannya,
 * sehingga user dianggap tamu: POST bookmark balas 401 "auth_required",
 * klien membatalkan tampilan dan memunculkan toast error. Di instance
 * yang kebetulan menangani login, semuanya normal — error-nya acak
 * tergantung instance yang melayani. Itulah sebabnya bookmark "error
 * terus" secara bergantian padahal logika bookmark-nya sendiri benar.
 *
 * PERBAIKAN: saat login, identitas (email dsb.) ikut disimpan di sesi
 * (lihat listener Login di AppServiceProvider). Sesi berdriver cookie
 * ikut bepergian bersama klien ke instance mana pun. Bila Auth kosong
 * tetapi sesi membawa identitas, middleware ini membuat ulang baris
 * user lokal di instance ini (firstOrCreate by EMAIL — kunci stabil)
 * lalu login kembali secara senyap. Operasi data sesudahnya tetap
 * memakai ID app_users Supabase via email, jadi ID lokal berapa pun
 * tidak masalah.
 */
class RehydrateAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() && $request->hasSession()) {
            $identity = $request->session()->get('auth_identity');
            if (is_array($identity) && !empty($identity['email'])) {
                try {
                    $user = User::firstOrCreate(
                        ['email' => $identity['email']],
                        [
                            'name' => $identity['name'] ?? 'Pengguna',
                            'password' => bcrypt(Str::random(32)),
                            'provider' => $identity['provider'] ?? null,
                            'provider_id' => $identity['provider_id'] ?? null,
                            'email_verified_at' => now(),
                        ]
                    );
                    Auth::login($user, true);
                } catch (\Throwable $e) {
                    Log::warning('RehydrateAuth gagal: ' . $e->getMessage());
                }
            }

            // SESI ZOMBI (keluhan user 2026-10-20: "unsave -> reload ->
            // tersimpan lagi terus begitu"): sesi dibuat SEBELUM perbaikan
            // ini ada, jadi cookie hanya membawa kunci login standar
            // (login_web_*) berisi ID user — tanpa auth_identity. Di
            // instance dingin baris user itu tidak ada dan identitas tak
            // bisa dipulihkan dari cookie lama, sehingga user terbaca
            // tamu secara ACAK: halaman Tersimpan menampilkan kartu dari
            // localStorage, unsave hanya menghapus lokal, reload berikut
            // (instance hangat) menampilkan lagi baris server yang tak
            // pernah terhapus. Obatnya: bersihkan sesi zombi SEKALI dan
            // arahkan ke login; login berikutnya menanam auth_identity
            // dan stabil permanen.
            if (!Auth::check()) {
                $guardKey = Auth::guard('web')->getName();
                if ($guardKey && $request->session()->has($guardKey)) {
                    try {
                        Auth::logout();
                    } catch (\Throwable $e) {
                        // abaikan — sesi memang sudah tak konsisten
                    }
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'status' => 'auth_required',
                            'message' => 'Sesi diperbarui, silakan masuk lagi.',
                        ], 401);
                    }
                    return redirect()->route('login')
                        ->with('error', 'Sesi Anda diperbarui. Silakan masuk lagi sekali saja — setelah itu semua tersimpan normal.');
                }
            }
        }

        return $next($request);
    }
}
