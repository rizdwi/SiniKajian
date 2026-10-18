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
Route::get('/peta', [KajianController::class, 'map'])->name('kajian.map');
Route::get('/kiblat', [KajianController::class, 'qibla'])->name('kajian.qibla');
Route::get('/tersimpan', [KajianController::class, 'saved'])->name('kajian.saved');

// Interaksi Kajian
Route::post('/kajian', [KajianController::class, 'store'])->name('kajian.store');
Route::post('/kajian/{kajian}/bookmark', [KajianController::class, 'toggleBookmark'])->name('kajian.bookmark');
Route::post('/kajian/{kajian}/kehadiran', [KajianController::class, 'logAttendance'])->name('kajian.attendance');
Route::post('/majelis/follow', [KajianController::class, 'toggleFollowMajelis'])->name('majelis.follow');
Route::get('/api/kajian', [KajianController::class, 'apiList'])->name('api.kajian');
Route::get('/api/kajians', [KajianController::class, 'apiList']);
Route::get('/kajians', [KajianController::class, 'apiList']);
Route::get('/kajian', [KajianController::class, 'apiList']);

// Autentikasi Pengguna & Kredensial
Route::redirect('/login', '/masuk');
Route::redirect('/register', '/daftar');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.post');
});

// Profil Jamaah (Screen 5)
Route::get('/profil', [AuthController::class, 'profile'])->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/profil/edit', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::post('/profil/edit', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profil', [AuthController::class, 'updateProfile']);
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
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

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('profile')->with('success', 'Email terverifikasi!');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('success', 'Link verifikasi dikirim ulang!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
