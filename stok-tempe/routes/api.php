<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\KonsinyasiController;
use App\Http\Controllers\Api\V1\PedagangController;
use App\Http\Controllers\Api\V1\ProdukController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Endpoint Publik (Login)
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Endpoint Terproteksi Token (Wajib Bearer Token -> 401 jika tanpa token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Endpoint Read-Only untuk Semua Role Login (Pemilik & Petugas Gudang)
        Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
        Route::get('/pedagang', [PedagangController::class, 'index'])->name('pedagang.index');
        Route::get('/konsinyasi', [KonsinyasiController::class, 'index'])->name('konsinyasi.index');
        Route::get('/konsinyasi/{kode_manifest}', [KonsinyasiController::class, 'show'])->name('konsinyasi.show');

        // Khusus Role: Pemilik (Manajemen Master Data)
        Route::middleware('role:pemilik')->group(function () {
            Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
            Route::post('/pedagang', [PedagangController::class, 'store'])->name('pedagang.store');
        });

        // Khusus Role: Petugas Gudang (Operasional Harian / POS)
        Route::middleware('role:petugas_gudang')->group(function () {
            Route::post('/produk/{kode_produk}/produksi', [ProdukController::class, 'produksi'])->name('produk.produksi');
            Route::post('/konsinyasi', [KonsinyasiController::class, 'store'])->name('konsinyasi.store');
            Route::post('/konsinyasi/{kode_manifest}/setoran', [KonsinyasiController::class, 'setoran'])->name('konsinyasi.setoran');
        });
    });
});