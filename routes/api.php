<?php

use App\Http\Controllers\KajianController;
use Illuminate\Support\Facades\Route;

// API publik read-only (data sama untuk semua pengunjung, tanpa sesi).
// Sengaja di grup 'api' (stateless, tanpa cookie sesi) agar respons bisa
// di-cache CDN Vercel — lihat header cache di KajianController::apiList.
// Tanpa throttle berbasis cache: edge cache menyerap beban, origin hanya
// menerima <=1 request per masa s-maxage per region.
Route::get('/kajian', [KajianController::class, 'apiList'])->name('api.kajian');
Route::get('/kajians', [KajianController::class, 'apiList']);

// Beacon pageview untuk menu admin Traffic (catat path saja, tanpa PII).
Route::post('/traffic/hit', [\App\Http\Controllers\TrafficController::class, 'hit']);
