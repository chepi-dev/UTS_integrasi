<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_pembukuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_manifest', 30)->index();
            $table->string('kode_pedagang', 20);
            $table->string('nama_pedagang', 100);
            $table->date('tanggal_transaksi');
            $table->string('jenis_jurnal', 30); // PEMASUKAN_SETORAN | BEBAN_WASTE
            $table->unsignedInteger('nominal');
            $table->string('keterangan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembukuan');
    }
};