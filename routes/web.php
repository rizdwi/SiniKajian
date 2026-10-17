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
Route::get('/peta', [KajianController::class, 'map'])->name('kajian.map');
Route::get('/kiblat', [KajianController::class, 'qibla'])->name('kajian.qibla');
Route::get('/tersimpan', [KajianController::class, 'saved'])->name('kajian.saved');

// Interaksi Kajian
Route::post('/kajian', [KajianController::class, 'store'])->name('kajian.store');
Route::post('/kajian/{kajian}/bookmark', [KajianController::class, 'toggleBookmark'])->name('kajian.bookmark');
Route::post('/kajian/{kajian}/kehadiran', [KajianController::class, 'logAttendance'])->name('kajian.attendance');
Route::get('/api/kajian', [KajianController::class, 'apiList'])->name('api.kajian');
Route::get('/api/kajians', [KajianController::class, 'apiList']);

// Autentikasi Pengguna & Kredensial
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::get('/profil', [AuthController::class, 'profile'])->name('profile');
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
});
