<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KajianController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SiniKajian
|--------------------------------------------------------------------------
*/

// Beranda & Modul Utama
Route::get('/', [KajianController::class, 'index'])->name('kajian.index');
Route::get('/kajian/{kajian}', [KajianController::class, 'show'])->name('kajian.show');
Route::get('/ustadz/{name}', [KajianController::class, 'ustadz'])->name('kajian.ustadz');
Route::get('/peta', [KajianController::class, 'map'])->name('kajian.map');
Route::get('/kiblat', [KajianController::class, 'qibla'])->name('kajian.qibla');
Route::get('/tersimpan', [KajianController::class, 'saved'])->name('kajian.saved');

// Interaksi Kajian
Route::post('/kajian', [KajianController::class, 'store'])->middleware('throttle:5,15')->name('kajian.store');
Route::post('/kajian/{id}/bookmark', [KajianController::class, 'toggleBookmark'])->middleware('throttle:30,1')->name('kajian.bookmark');
Route::post('/api/bookmarks/sync', [KajianController::class, 'syncBookmarks'])->middleware('throttle:30,1')->name('api.bookmarks.sync');
Route::post('/kajian/{id}/kehadiran', [KajianController::class, 'logAttendance'])->middleware('throttle:20,1')->name('kajian.attendance');
Route::post('/kehadiran/bersihkan', [KajianController::class, 'clearAttendances'])->middleware('throttle:10,1')->name('attendance.clear');
Route::post('/majelis/follow', [KajianController::class, 'toggleFollowMajelis'])->middleware('throttle:30,1')->name('majelis.follow');
Route::get('/api/kajian', [KajianController::class, 'apiList'])->middleware('throttle:60,1')->name('api.kajian');
Route::get('/api/kajians', [KajianController::class, 'apiList'])->middleware('throttle:60,1');
Route::get('/kajians', [KajianController::class, 'apiList'])->middleware('throttle:60,1');
Route::get('/kajian', [KajianController::class, 'apiList'])->middleware('throttle:60,1');

// Autentikasi Pengguna & Kredensial
Route::redirect('/login', '/masuk');
Route::redirect('/register', '/daftar');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:3,10')->name('register.post');
    Route::get('/lupa-sandi', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/lupa-sandi', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,15')->name('password.email');
    Route::get('/reset-sandi/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-sandi', [AuthController::class, 'resetPassword'])->middleware('throttle:5,15')->name('password.update');
});

// Profil Jamaah (Screen 5)
Route::get('/profil', [AuthController::class, 'profile'])->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/profil/edit', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::post('/profil/edit', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profil', [AuthController::class, 'updateProfile']);
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    // Admin Moderasi Kajian
    Route::prefix('admin')->group(function () {
        Route::get('/kajian', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.kajian.index');
        Route::post('/kajian/{kajian}/verify', [\App\Http\Controllers\AdminController::class, 'verify'])->name('admin.kajian.verify');
        Route::delete('/kajian/{kajian}', [\App\Http\Controllers\AdminController::class, 'destroy'])->name('admin.kajian.destroy');
    });
});

// Sitemap XML
Route::get('/sitemap.xml', function () {
    $kajians = \App\Models\Kajian::where('verified', true)->select('id', 'updated_at')->get();
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $xml .= '  <url><loc>' . url('/') . '</loc><changefreq>hourly</changefreq><priority>1.0</priority></url>' . "\n";
    $xml .= '  <url><loc>' . url('/peta') . '</loc><changefreq>hourly</changefreq><priority>0.8</priority></url>' . "\n";
    foreach ($kajians as $k) {
        $xml .= '  <url><loc>' . url("/kajian/{$k->id}") . '</loc><lastmod>' . $k->updated_at->toDateString() . '</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>' . "\n";
    }
    $xml .= '</urlset>';
    return response($xml, 200)->header('Content-Type', 'application/xml');
});

// Social login (Google & Facebook)
Route::get('/auth/{provider}', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])->name('social.callback');

// Email verification
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::find($id);

    if (!$user || !hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Tautan verifikasi tidak sah.');
    }

    if (!$request->hasValidSignature()) {
        abort(403, 'Tautan verifikasi telah kadaluarsa atau tidak valid.');
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));

        // Sinkronisasi status email_verified_at ke Supabase jika aktif
        try {
            $svc = new \App\Services\SupabaseUserService();
            if ($svc->enabled()) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => config('services.supabase.service_key'),
                    'Authorization' => 'Bearer ' . config('services.supabase.service_key'),
                    'Content-Type' => 'application/json',
                ])->timeout(10)->patch(config('services.supabase.url') . '/rest/v1/app_users?email=eq.' . urlencode($user->email), [
                    'email_verified_at' => now()->toIso8601String(),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Supabase sync email_verified_at error: ' . $e->getMessage());
        }
    }

    if (!\Illuminate\Support\Facades\Auth::check()) {
        \Illuminate\Support\Facades\Auth::login($user);
    }

    return redirect()->route('profile')->with('success', 'Alhamdulillah! Alamat email Anda berhasil diverifikasi.');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    try {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Tautan verifikasi baru berhasil dikirimkan ke kotak masuk email Anda.');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Kirim ulang email verifikasi gagal: ' . $e->getMessage());
        return back()->with('error', 'Gagal mengirim email: periksa pengaturan mailer (SMTP/Resend) di server.');
    }
})->middleware(['auth', 'throttle:3,10'])->name('verification.send');

// Favicon fallback handler
Route::get('/favicon.ico', function () {
    $path = public_path('favicon.ico');
    if (!file_exists($path)) {
        $path = base_path('favicon.ico');
    }
    return response()->file($path, [
        'Content-Type' => 'image/x-icon',
        'Cache-Control' => 'public, max-age=604800, immutable',
    ]);
});

// Webhook: trigger sync Supabase -> SQLite (dipanggil cron setelah insert)
Route::post('/webhook/sync-kajian', function (\Illuminate\Http\Request $request) {
    $secret = env('SYNC_WEBHOOK_SECRET');
    if (!$secret || !hash_equals($secret, (string) $request->header('X-Sync-Secret', ''))) {
        abort(403, 'Unauthorized');
    }
    \Illuminate\Support\Facades\Artisan::call('kajian:sync-supabase');
    // Clear Supabase cache agar data baru langsung tampil
    \App\Services\SupabaseKajianService::clearCache();
    return response()->json(['ok' => true]);
});

// Web Push Notifications
Route::post('/api/push/subscribe', [App\Http\Controllers\PushController::class, 'subscribe'])->middleware('throttle:10,1');
Route::post('/api/push/unsubscribe', [App\Http\Controllers\PushController::class, 'unsubscribe'])->middleware('throttle:10,1');
Route::post('/api/push/send', [App\Http\Controllers\PushController::class, 'send'])->middleware('throttle:60,1');
