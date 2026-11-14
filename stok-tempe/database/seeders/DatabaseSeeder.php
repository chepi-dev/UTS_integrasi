<?php

namespace Database\Seeders;

use App\Models\Pedagang;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Produk::updateOrCreate(
            ['kode_produk' => 'TAHU-001'],
            [
                'nama_produk' => 'Tahu Putih',
                'jenis_produk' => 'tahu',
                'harga_satuan' => 4000,
                'stok_gudang' => 100,
            ]
        );

        Produk::updateOrCreate(
            ['kode_produk' => 'TEMP-001'],
            [
                'nama_produk' => 'Tempe Papan',
                'jenis_produk' => 'tempe',
                'harga_satuan' => 5000,
                'stok_gudang' => 50,
            ]
        );

        Pedagang::updateOrCreate(
            ['kode_pedagang' => 'PDG-001'],
            [
                'nama_pedagang' => 'Pak Budi',
                'no_telepon' => '081234567890',
                'wilayah_jualan' => 'Pasar Baru',
                'aktif' => true,
            ]
        );
    }
}