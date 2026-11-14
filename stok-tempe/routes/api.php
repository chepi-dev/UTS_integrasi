<?php

use App\Http\Controllers\Api\V1\KonsinyasiController;
use App\Http\Controllers\Api\V1\PedagangController;
use App\Http\Controllers\Api\V1\ProdukController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Produk & Produksi Gudang
    Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
    Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
    Route::post('/produk/{kode_produk}/produksi', [ProdukController::class, 'produksi'])->name('produk.produksi');

    // Mitra Pedagang
    Route::get('/pedagang', [PedagangController::class, 'index'])->name('pedagang.index');
    Route::post('/pedagang', [PedagangController::class, 'store'])->name('pedagang.store');

    // Siklus Konsinyasi (Ambil Pagi & Setor Sore)
    Route::get('/konsinyasi', [KonsinyasiController::class, 'index'])->name('konsinyasi.index');
    Route::post('/konsinyasi', [KonsinyasiController::class, 'store'])->name('konsinyasi.store');
    Route::post('/konsinyasi/{kode_manifest}/setoran', [KonsinyasiController::class, 'setoran'])->name('konsinyasi.setoran');
});