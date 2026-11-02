<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\KategoriProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukApiTest extends TestCase
{
    use RefreshDatabase;

    private KategoriProduk $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat kategori dummy untuk setiap test case
        $this->kategori = KategoriProduk::create([
            'nama_kategori' => 'Tahu Basah',
            'daya_tahan_hari' => 2,
        ]);
    }

    public function test_tambah_produk_baru(): void
    {
        $payload = [
            'kategori_id' => $this->kategori->id,
            'nama_produk' => 'Tahu Sutra Putih',
            'satuan' => 'papan',
            'harga_satuan' => 5000,
            'stok_awal' => 50,
            'stok_minimum' => 5,
        ];

        $response = $this->postJson('/api/v1/produk', $payload);
        
        $response->assertStatus(201);
        // PERBAIKAN: Gunakan assertHeader, bukan assertHasHeader
        $response->assertHeader('Location'); 
        $response->assertJson([
            'nama_produk' => 'Tahu Sutra Putih',
            'satuan' => 'papan',
            'jumlah_stok' => 50,
            'status_stok' => 'aman',
        ]);
    }

    public function test_penyesuaian_stok_rusak_basi(): void
    {
        $produk = Produk::create([
            'kode_produk' => 'TAH-001',
            'kategori_id' => $this->kategori->id, // PERBAIKAN: Ambil ID dari setup
            'nama_produk' => 'Tahu Putih',
            'satuan' => 'papan',
            'harga_satuan' => 4000,
            'jumlah_stok' => 10,
            'stok_minimum' => 2,
        ]);

        $response = $this->postJson('/api/v1/produk/TAH-001/penyesuaian-stok', [
            'jenis_penyesuaian' => 'rusak_basi',
            'jumlah' => 3,
            'keterangan' => 'Tahu berjamur karena suhu lembab',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['jumlah_stok' => 7]);
        $this->assertDatabaseHas('riwayat_stok', [
            'kode_produk' => 'TAH-001',
            'jenis_penyesuaian' => 'rusak_basi',
            'jumlah' => 3,
        ]);
    }

    public function test_409_stok_tidak_cukup(): void
    {
        Produk::create([
            'kode_produk' => 'TAH-001',
            'kategori_id' => $this->kategori->id, // PERBAIKAN: Ambil ID dari setup
            'nama_produk' => 'Tahu Putih',
            'satuan' => 'papan',
            'harga_satuan' => 4000,
            'jumlah_stok' => 2,
        ]);

        $response = $this->postJson('/api/v1/produk/TAH-001/penyesuaian-stok', [
            'jenis_penyesuaian' => 'penjualan',
            'jumlah' => 10,
        ]);

        $response->assertStatus(409);
        $response->assertHeader('Content-Type', 'application/problem+json');
    }
}