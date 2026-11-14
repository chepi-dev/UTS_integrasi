<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedagang', function (Blueprint $table) {
            $table->string('kode_pedagang', 20)->primary();
            $table->string('nama_pedagang', 100);
            $table->string('no_telepon', 20);
            $table->string('wilayah_jualan', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedagang');
    }
};