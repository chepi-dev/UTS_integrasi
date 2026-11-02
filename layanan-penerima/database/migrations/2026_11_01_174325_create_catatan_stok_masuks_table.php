<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_stok_masuk', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk');
            $table->string('nama_produk');
            $table->enum('jenis_penyesuaian', ['masuk_produksi', 'penjualan', 'rusak_basi', 'koreksi_opname']);
            $table->integer('jumlah');
            $table->timestamp('terjadi_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_stok_masuk');
    }
};