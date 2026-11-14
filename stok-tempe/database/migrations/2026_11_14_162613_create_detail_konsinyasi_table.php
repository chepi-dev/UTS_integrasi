<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_konsinyasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_manifest', 30);
            $table->string('kode_produk', 20);
            $table->unsignedInteger('harga_satuan_snapshot');
            $table->unsignedInteger('jumlah_dibawa');
            $table->unsignedInteger('jumlah_terjual')->default(0);
            $table->unsignedInteger('jumlah_retur_layak')->default(0);
            $table->unsignedInteger('jumlah_rusak')->default(0);
            $table->timestamps();

            $table->unique(['kode_manifest', 'kode_produk']);

            $table->foreign('kode_manifest')
                ->references('kode_manifest')
                ->on('konsinyasi')
                ->onDelete('cascade');

            $table->foreign('kode_produk')
                ->references('kode_produk')
                ->on('produk')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_konsinyasi');
    }
};