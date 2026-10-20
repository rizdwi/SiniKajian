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
        }

        return $next($request);
    }
}
