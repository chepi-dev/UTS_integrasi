<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->string('kode_produk')->primary();
            $table->foreignId('kategori_id')->constrained('kategori_produk')->onDelete('cascade');
            $table->string('nama_produk');
            $table->enum('satuan', ['papan', 'potong', 'biji', 'kg'])->default('papan');
            $table->integer('harga_satuan');
            $table->integer('jumlah_stok')->default(0);
            $table->integer('stok_minimum')->default(10);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('terakhir_diperbarui')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};