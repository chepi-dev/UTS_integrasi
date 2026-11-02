<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriProduk;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        KategoriProduk::create(['nama_kategori' => 'Tahu Basah', 'daya_tahan_hari' => 2]);
        KategoriProduk::create(['nama_kategori' => 'Tempe Daun', 'daya_tahan_hari' => 4]);
        KategoriProduk::create(['nama_kategori' => 'Tempe Plastik', 'daya_tahan_hari' => 5]);
    }
}