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

        if (config('app.env') === 'production' || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || env('VERCEL') || app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Daftarkan driver email custom Resend API (HTTP REST, optimal untuk serverless Vercel)
        Mail::extend('resend', function (array $config = []) {
            $key = config('services.resend.key') ?: env('RESEND_API_KEY') ?: getenv('RESEND_API_KEY') ?: ($_SERVER['RESEND_API_KEY'] ?? '');
            return new \App\Mail\ResendTransport($key);
        });

        // Kustomisasi pesan email verifikasi akun (bahasa Indonesia & ramah jamaah)
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
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
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

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
