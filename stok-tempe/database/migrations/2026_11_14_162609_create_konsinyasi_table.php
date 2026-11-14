<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konsinyasi', function (Blueprint $table) {
            $table->string('kode_manifest', 30)->primary();
            $table->string('kode_pedagang', 20);
            $table->date('tanggal_konsinyasi');
            $table->string('status', 20)->default('BERJALAN')->index(); // BERJALAN | SELESAI
            $table->unsignedInteger('total_tagihan_setoran')->default(0);
            $table->unsignedInteger('total_kerugian_waste')->default(0);
            $table->timestamp('waktu_ambil');
            $table->timestamp('waktu_setor')->nullable();
            $table->timestamps();

            $table->foreign('kode_pedagang')
                ->references('kode_pedagang')
                ->on('pedagang')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konsinyasi');
    }
};