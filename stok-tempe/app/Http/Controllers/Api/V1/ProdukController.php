<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateStokRequest;
use App\Http\Resources\ProdukResource;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\RiwayatStok;
use App\Exceptions\StokHabisException;
use App\Http\ApiProblem;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index()
    {
        $data = Produk::with('kategori')->orderBy('nama_produk')->get();
        return response()->json(ProdukResource::collection($data));
    }

    public function store(StoreProdukRequest $request)
    {
        $valid = $request->validated();
        $kategori = KategoriProduk::findOrFail($valid['kategori_id']);

        // Generate ID Aman (Mengambil angka terbesar)
        $prefix = strtoupper(substr($kategori->nama_kategori, 0, 3));
        $lastProduk = Produk::where('kode_produk', 'LIKE', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(kode_produk, 5) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastProduk) {
            $parts = explode('-', $lastProduk->kode_produk);
            $nextNum = ((int) end($parts)) + 1;
        }

        $kode = $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $produk = Produk::create([
            'kode_produk' => $kode,
            'kategori_id' => $valid['kategori_id'],
            'nama_produk' => $valid['nama_produk'],
            'satuan' => $valid['satuan'],
            'harga_satuan' => $valid['harga_satuan'],
            'jumlah_stok' => $valid['stok_awal'],
            'stok_minimum' => $valid['stok_minimum'] ?? 10,
            'terakhir_diperbarui' => now(),
        ]);

        return response()
            ->json(new ProdukResource($produk->load('kategori')), 201)
            ->header('Location', route('api.v1.produk.show', ['kode_produk' => $produk->kode_produk]));
    }

    public function show(string $kode_produk)
    {
        $produk = Produk::with('kategori')->find($kode_produk);

        if (!$produk) {
            return ApiProblem::respond(
                404,
                'Produk tidak ditemukan',
                sprintf('Produk dengan kode %s tidak tersedia.', $kode_produk)
            );
        }

        return response()->json(new ProdukResource($produk));
    }

    public function sesuaikanStok(UpdateStokRequest $request, string $kode_produk)
    {
        $valid = $request->validated();
        $jenis = $valid['jenis_penyesuaian'];
        $jumlah = $valid['jumlah'];
        $keterangan = $valid['keterangan'] ?? null;

        try {
            $produk = DB::transaction(function () use ($kode_produk, $jenis, $jumlah, $keterangan) {
                // Lock database baris ini (Pessimistic Locking)
                $produk = Produk::where('kode_produk', $kode_produk)->lockForUpdate()->first();

                if (!$produk) {
                    return null;
                }

                $stokSebelum = $produk->jumlah_stok;

                if (in_array($jenis, ['penjualan', 'rusak_basi'])) {
                    if ($produk->jumlah_stok < $jumlah) {
                        throw new StokHabisException(
                            sprintf('Stok %s tidak cukup. Sisa stok: %d, diminta: %d', $produk->nama_produk, $produk->jumlah_stok, $jumlah)
                        );
                    }
                    $produk->jumlah_stok -= $jumlah;
                } elseif ($jenis === 'masuk_produksi') {
                    $produk->jumlah_stok += $jumlah;
                } elseif ($jenis === 'koreksi_opname') {
                    $produk->jumlah_stok = $jumlah;
                }

                $produk->terakhir_diperbarui = now();
                $produk->save();

                // Catat Log Riwayat
                RiwayatStok::create([
                    'kode_produk' => $produk->kode_produk,
                    'jenis_penyesuaian' => $jenis,
                    'jumlah' => $jumlah,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $produk->jumlah_stok,
                    'keterangan' => $keterangan,
                ]);

                return $produk;
            });

            if (!$produk) {
                return ApiProblem::respond(
                    404,
                    'Produk tidak ditemukan',
                    sprintf('Produk dengan kode %s tidak tersedia.', $kode_produk)
                );
            }

        } catch (StokHabisException $e) {
            return ApiProblem::respond(409, 'Stok tidak cukup', $e->getMessage());
        }

        return response()->json(new ProdukResource($produk->load('kategori')));
    }
}