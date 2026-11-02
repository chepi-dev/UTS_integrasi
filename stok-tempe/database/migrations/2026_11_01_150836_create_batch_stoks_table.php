<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_stok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk');
            $table->foreign('kode_produk')->references('kode_produk')->on('produk')->onDelete('cascade');
            $table->string('nomor_batch')->unique();
            $table->date('tanggal_produksi');
            $table->date('tanggal_kadaluarsa');
            $table->integer('jumlah_stok');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_stok');
    }
};