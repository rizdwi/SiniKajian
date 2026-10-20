<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect ke Google OAuth dengan state anti-CSRF.
     */
    public function redirect(string $provider): RedirectResponse
    {
        // Hanya Google yang didukung (Facebook dibatalkan permanen per keputusan user)
        if ($provider !== 'google') {
            abort(404);
        }

        $config = config('services.google');

        // Force HTTPS untuk redirect URI (Vercel proxy)
        $redirectUri = str_replace('http://', 'https://', route('social.callback', $provider));

        // Generate state anti-CSRF, simpan di session
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$params}");
    }

    /**
     * Handle callback dari Google OAuth.
     */
    public function callback(string $provider): RedirectResponse
    {
        if ($provider !== 'google') {
            abort(404);
        }

        // Validasi state anti-CSRF
        $state = request('state');
        $expectedState = session()->pull('oauth_state');
        if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
            return redirect()->route('login')->withErrors(['social' => 'Sesi login tidak valid. Silakan coba lagi.']);
        }

        $code = request('code');
        if (!$code) {
            return redirect()->route('login')->withErrors(['social' => 'Login dibatalkan.']);
        }

        $config = config('services.google');
        $redirectUri = str_replace('http://', 'https://', route('social.callback', $provider));

        try {
            $tokenRes = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);
            $accessToken = $tokenRes->json('access_token');
            if (!$accessToken) {
                throw new \RuntimeException('Gagal menukar kode otorisasi.');
            }

            $userRes = Http::withToken($accessToken)->timeout(10)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            $socialUser = $userRes->json();

            // Guard: pastikan field penting ada
            $providerId = $socialUser['sub'] ?? null;
            $email = $socialUser['email'] ?? null;
            $emailVerified = $socialUser['email_verified'] ?? false;
            $name = $socialUser['name'] ?? 'Pengguna Google';

            if (!$providerId || !$email) {
                throw new \RuntimeException('Respons Google tidak lengkap.');
            }

            // Tolak email yang belum terverifikasi Google
            if ($emailVerified !== true) {
                return redirect()->route('login')->withErrors([
                    'social' => 'Email Google Anda belum terverifikasi. Silakan verifikasi dulu di akun Google Anda.',
                ]);
            }

            // Cari atau buat user (sinkron ke Supabase agar persisten di Vercel)
            $sbSvc = new SupabaseUserService();
            $sbUser = $sbSvc->enabled() ? $sbSvc->findByEmail($email) : null;

            // PENTING: cocokkan provider_id DAN email sekaligus. Dulu cukup
            // provider_id saja — dan file SQLite yang ter-commit memuat user
            // TES dari pengujian API 18 Okt yang kebetulan membawa
            // provider_id Google milik user asli, sehingga login user asli
            // selalu mendarat di akun "OAuth User Test" (terjadi berulang
            // 19 & 20 Okt). Dengan email ikut dicocokkan, baris basi seperti
            // itu tidak akan pernah bisa membajak login lagi.
            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->where('email', $email)
                ->first();

            if (!$user && $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update(['provider' => $provider, 'provider_id' => $providerId]);
                }
            }

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt(Str::random(32)),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'email_verified_at' => now(),
                ]);

                // Sinkron ke Supabase
                if ($sbSvc->enabled()) {
                    try {
                        $sbUser = $sbSvc->findOrCreate([
                            'name' => $name,
                            'email' => $email,
                            'password' => $user->password,
                            'provider' => $provider,
                            'provider_id' => $providerId,
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Supabase sync OAuth gagal: ' . $e->getMessage());
                    }
                }
            }

            // Muat avatar dari Supabase agar persisten antar login
            if ($sbSvc->enabled()) {
                try {
                    $sbUser = $sbUser ?? $sbSvc->findByEmail($email);
                    if ($sbUser && !empty($sbUser['avatar'])) {
                        $user->avatar = $sbUser['avatar'];
                        $user->save();
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Load avatar Supabase gagal: ' . $e->getMessage());
                }
            }

            Auth::login($user, true);
            return redirect()->intended(route('kajian.index'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Social OAuth Error [' . $provider . ']: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['social' => 'Login dengan Google gagal diproses. Silakan coba kembali atau gunakan login email.']);
        }
    }
}
