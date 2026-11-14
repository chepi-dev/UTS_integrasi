<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->string('kode_produk', 20)->primary();
            $table->string('nama_produk', 100);
            $table->string('jenis_produk', 20); // tahu | tempe
            $table->unsignedInteger('harga_satuan');
            $table->unsignedInteger('stok_gudang')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};