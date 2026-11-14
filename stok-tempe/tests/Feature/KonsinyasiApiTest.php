<?php

namespace Tests\Feature;

use App\Models\Pedagang;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonsinyasiApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedDataAwal(): void
    {
        Produk::create([
            'kode_produk' => 'TAHU-001',
            'nama_produk' => 'Tahu Putih',
            'jenis_produk' => 'tahu',
            'harga_satuan' => 4000,
            'stok_gudang' => 100,
        ]);

        Produk::create([
            'kode_produk' => 'TEMP-001',
            'nama_produk' => 'Tempe Papan',
            'jenis_produk' => 'tempe',
            'harga_satuan' => 5000,
            'stok_gudang' => 50,
        ]);

        Pedagang::create([
            'kode_pedagang' => 'PDG-001',
            'nama_pedagang' => 'Pak Budi',
            'no_telepon' => '081234567890',
            'wilayah_jualan' => 'Pasar Baru',
            'aktif' => true,
        ]);
    }

    public function test_tambah_produk_dan_pencatatan_produksi_harian(): void
    {
        $resCreate = $this->postJson('/api/v1/produk', [
            'nama_produk' => 'Tahu Kuning',
            'jenis_produk' => 'tahu',
            'harga_satuan' => 4500,
            'stok_awal' => 40,
        ]);

        $resCreate->assertStatus(201)
            ->assertJson([
                'kode_produk' => 'TAHU-001',
                'stok_gudang' => 40,
                'tersedia' => true,
            ]);

        $resProduksi = $this->postJson('/api/v1/produk/TAHU-001/produksi', [
            'jumlah_produksi' => 60,
            'catatan' => 'Produksi kloter pagi',
        ]);

        $resProduksi->assertStatus(200)
            ->assertJson([
                'kode_produk' => 'TAHU-001',
                'stok_gudang' => 100,
            ]);
    }

    public function test_siklus_lengkap_konsinyasi_pagi_hingga_setoran_sore(): void
    {
        $this->seedDataAwal();

        // 1. Pengambilan Pagi (30 Tahu, 20 Tempe)
        $resAmbil = $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [
                ['kode_produk' => 'TAHU-001', 'jumlah_dibawa' => 30],
                ['kode_produk' => 'TEMP-001', 'jumlah_dibawa' => 20],
            ],
        ]);

        $resAmbil->assertStatus(201)
            ->assertHeader('Location')
            ->assertJson([
                'status' => 'BERJALAN',
                'pedagang' => ['kode_pedagang' => 'PDG-001'],
            ]);

        $kodeManifest = $resAmbil->json('kode_manifest');

        // Cek stok gudang berkurang setelah pengambilan pagi
        $this->assertDatabaseHas('produk', ['kode_produk' => 'TAHU-001', 'stok_gudang' => 70]);
        $this->assertDatabaseHas('produk', ['kode_produk' => 'TEMP-001', 'stok_gudang' => 30]);

        // BR-04: Simulasi perubahan harga master di siang hari (tidak boleh memengaruhi manifest berjalan)
        Produk::where('kode_produk', 'TAHU-001')->update(['harga_satuan' => 9999]);

        // 2. Setoran Sore (Tahu: 22 laku, 5 retur, 3 rusak | Tempe: 18 laku, 2 retur, 0 rusak)
        $resSetor = $this->postJson("/api/v1/konsinyasi/{$kodeManifest}/setoran", [
            'items' => [
                [
                    'kode_produk' => 'TAHU-001',
                    'jumlah_terjual' => 22,
                    'jumlah_retur_layak' => 5,
                    'jumlah_rusak' => 3,
                ],
                [
                    'kode_produk' => 'TEMP-001',
                    'jumlah_terjual' => 18,
                    'jumlah_retur_layak' => 2,
                    'jumlah_rusak' => 0,
                ],
            ],
        ]);

        $resSetor->assertStatus(200)
            ->assertJson([
                'kode_manifest' => $kodeManifest,
                'status' => 'SELESAI',
                'total_tagihan_setoran' => 178000, // (22 * 4000) + (18 * 5000)
                'total_kerugian_waste' => 12000,   // (3 * 4000) + (0 * 5000)
            ]);

        // Cek stok gudang bertambah kembali dari barang retur layak jual
        $this->assertDatabaseHas('produk', ['kode_produk' => 'TAHU-001', 'stok_gudang' => 75]);
        $this->assertDatabaseHas('produk', ['kode_produk' => 'TEMP-001', 'stok_gudang' => 32]);
    }

    public function test_br01_gagal_ambil_jika_stok_gudang_tidak_cukup(): void
    {
        $this->seedDataAwal();

        $response = $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [
                ['kode_produk' => 'TAHU-001', 'jumlah_dibawa' => 150], // Stok cuma 100
            ],
        ]);

        $response->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_br03_gagal_ambil_jika_pedagang_masih_punya_manifest_berjalan(): void
    {
        $this->seedDataAwal();

        $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [['kode_produk' => 'TAHU-001', 'jumlah_dibawa' => 10]],
        ])->assertStatus(201);

        // Ambil lagi sebelum manifest pertama disetor
        $response = $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [['kode_produk' => 'TEMP-001', 'jumlah_dibawa' => 5]],
        ]);

        $response->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_br02_gagal_setor_jika_rekonsiliasi_tidak_seimbang(): void
    {
        $this->seedDataAwal();

        $resAmbil = $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [['kode_produk' => 'TAHU-001', 'jumlah_dibawa' => 30]],
        ]);

        $kodeManifest = $resAmbil->json('kode_manifest');

        // Total 22 + 5 + 2 = 29 (kurang 1 dari 30)
        $response = $this->postJson("/api/v1/konsinyasi/{$kodeManifest}/setoran", [
            'items' => [
                [
                    'kode_produk' => 'TAHU-001',
                    'jumlah_terjual' => 22,
                    'jumlah_retur_layak' => 5,
                    'jumlah_rusak' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_br05_gagal_setor_ulang_manifest_yang_sudah_selesai(): void
    {
        $this->seedDataAwal();

        $resAmbil = $this->postJson('/api/v1/konsinyasi', [
            'kode_pedagang' => 'PDG-001',
            'items' => [['kode_produk' => 'TAHU-001', 'jumlah_dibawa' => 10]],
        ]);

        $kodeManifest = $resAmbil->json('kode_manifest');

        $payloadSetor = [
            'items' => [
                [
                    'kode_produk' => 'TAHU-001',
                    'jumlah_terjual' => 10,
                    'jumlah_retur_layak' => 0,
                    'jumlah_rusak' => 0,
                ],
            ],
        ];

        $this->postJson("/api/v1/konsinyasi/{$kodeManifest}/setoran", $payloadSetor)->assertStatus(200);
        $this->postJson("/api/v1/konsinyasi/{$kodeManifest}/setoran", $payloadSetor)
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json');
    }
}