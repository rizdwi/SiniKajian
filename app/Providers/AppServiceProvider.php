<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');

        // Cache store bersama via Supabase (audit 2026-10-21, H3) — membuat
        // Cache::remember & RateLimiter berlaku lintas instance Vercel.
        \Illuminate\Support\Facades\Cache::extend('supabase', function ($app, array $config) {
            // WAJIB Repository: RateLimiter & Cache::remember menuntut
            // kontrak Repository — Store mentah membuat mereka TypeError.
            return new \Illuminate\Cache\Repository(new \App\Cache\SupabaseStore());
        });

        // Simpan identitas login di sesi (driver cookie) agar sesi tetap
        // dikenali di SEMUA instance Vercel. Tanpa ini, Auth hanya membawa
        // ID baris SQLite lokal — dan tiap instance punya salinan SQLite
        // sendiri (lihat App\Http\Middleware\RehydrateAuth). Email adalah
        // kunci stabilnya. Listener ini menangkap SEMUA jalur login
        // (Google OAuth, email+sandi, register) di satu titik.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            try {
                if (request()->hasSession() && $event->user) {
                    session(['auth_identity' => [
                        'email' => $event->user->email,
                        'name' => $event->user->name,
                        'provider' => $event->user->provider ?? null,
                        'provider_id' => $event->user->provider_id ?? null,
                    ]]);
                }
            } catch (\Throwable $e) {
                // Jangan pernah menggagalkan login hanya karena ini gagal.
            }
        });
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            try {
                if (request()->hasSession()) {
                    session()->forget('auth_identity');
                }
            } catch (\Throwable $e) {
            }
        });

        if (config('app.env') === 'production' || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || env('VERCEL') || app()->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl('https://majelisku.web.id');
        }

        // Daftarkan driver email custom Resend API (HTTP REST, optimal untuk serverless Vercel)
        Mail::extend('resend', function (array $config = []) {
            $key = config('services.resend.key') ?: env('RESEND_API_KEY') ?: getenv('RESEND_API_KEY') ?: ($_SERVER['RESEND_API_KEY'] ?? '');
            return new \App\Mail\ResendTransport($key);
        });

        // Kustomisasi pesan email verifikasi akun (bahasa Indonesia & ramah jamaah)
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
                $url = str_replace(['http://localhost:8000', 'http://localhost', 'http://127.0.0.1:8000'], 'https://majelisku.web.id', $url);
            }

            return (new MailMessage)
                ->subject('Verifikasi Alamat Email Anda - Majelisku')
                ->greeting('Assalamu\'alaikum, ' . ($notifiable->name ?? 'Jamaah Majelisku') . '!')
                ->line('Terima kasih telah bergabung di Majelisku, direktori jadwal kajian & majelis ilmu sunnah.')
                ->line('Klik tombol di bawah ini untuk memverifikasi dan mengaktifkan akun Anda:')
                ->action('Verifikasi Email Saya', $url)
                ->line('Tautan verifikasi ini aman dan akan kadaluarsa dalam 60 menit.')
                ->line('Jika Anda tidak merasa mendaftarkan akun di Majelisku, abaikan saja email ini.')
                ->salutation('Wassalamu\'alaikum Warahmatullahi Wabarakatuh,' . "\n" . 'Tim Majelisku');
        });

        // Kustomisasi pesan email reset kata sandi
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $domain = (app()->environment('production') || env('VERCEL') || isset($_SERVER['VERCEL']))
                ? 'https://majelisku.web.id'
                : (config('app.url') && !str_contains(config('app.url'), 'localhost') ? config('app.url') : 'https://majelisku.web.id');

            $url = rtrim($domain, '/') . '/reset-sandi/' . $token . '?email=' . urlencode($notifiable->getEmailForPasswordReset());

            return (new MailMessage)
                ->subject('Atur Ulang Kata Sandi Akun Majelisku')
                ->greeting('Assalamu\'alaikum, ' . ($notifiable->name ?? 'Jamaah Majelisku') . '!')
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Majelisku Anda.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line('Tautan pengaturan ulang sandi ini akan kadaluarsa dalam 60 menit.')
                ->line('Jika Anda tidak meminta perubahan kata sandi, abaikan email ini.')
                ->salutation('Wassalamu\'alaikum Warahmatullahi Wabarakatuh,' . "\n" . 'Tim Majelisku');
        });
    }
}
