<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_stok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk');
            $table->foreign('kode_produk')->references('kode_produk')->on('produk')->onDelete('cascade');
            $table->enum('jenis_penyesuaian', ['masuk_produksi', 'penjualan', 'rusak_basi', 'koreksi_opname']);
            $table->integer('jumlah');
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_stok');
    }
};