<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect ke provider OAuth.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, ['facebook', 'google'])) {
            abort(404);
        }

        $config = config("services.{$provider}");

        // Force HTTPS untuk redirect URI (Vercel proxy)
        $redirectUri = str_replace('http://', 'https://', route('social.callback', $provider));

        if ($provider === 'google') {
            $params = http_build_query([
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'access_type' => 'online',
                'prompt' => 'select_account',
            ]);
            return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$params}");
        }

        // facebook
        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'email,public_profile',
        ]);
        return redirect("https://www.facebook.com/v18.0/dialog/oauth?{$params}");
    }

    /**
     * Handle callback dari provider.
     */
    public function callback(string $provider): RedirectResponse
    {
        if (!in_array($provider, ['facebook', 'google'])) {
            abort(404);
        }

        $code = request('code');
        if (!$code) {
            return redirect()->route('login')->withErrors(['social' => 'Login dibatalkan.']);
        }

        $config = config("services.{$provider}");
        $redirectUri = str_replace('http://', 'https://', route('social.callback', $provider));

        try {
            if ($provider === 'google') {
                $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ]);
                $accessToken = $tokenRes->json('access_token');

                $userRes = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
                $socialUser = $userRes->json();
                $providerId = $socialUser['sub'];
                $email = $socialUser['email'] ?? null;
                $name = $socialUser['name'] ?? 'Pengguna Google';
            } else {
                $tokenRes = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirectUri,
                ]);
                $accessToken = $tokenRes->json('access_token');

                $userRes = Http::get('https://graph.facebook.com/v18.0/me', [
                    'fields' => 'id,name,email',
                    'access_token' => $accessToken,
                ]);
                $socialUser = $userRes->json();
                $providerId = $socialUser['id'];
                $email = $socialUser['email'] ?? null;
                $name = $socialUser['name'] ?? 'Pengguna Facebook';
            }

            // Cari atau buat user
            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
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
                    'email' => $email ?? "{$provider}_{$providerId}@sinikajian.local",
                    'password' => bcrypt(Str::random(32)),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                ]);
            }

            Auth::login($user, true);
            return redirect()->intended(route('kajian.index'));

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => 'Login gagal: ' . $e->getMessage()]);
        }
    }
}
