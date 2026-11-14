<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\ApiProblem;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProduksiProdukRequest;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Resources\ProdukResource;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index()
    {
        $data = Produk::orderBy('kode_produk')->get();
        return response()->json(ProdukResource::collection($data));
    }

    public function store(StoreProdukRequest $request)
    {
        $valid = $request->validated();

        $kode = strtoupper(substr($valid['jenis_produk'], 0, 4)) . '-' . str_pad(
            Produk::where('jenis_produk', $valid['jenis_produk'])->count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        $produk = Produk::create([
            'kode_produk' => $kode,
            'nama_produk' => $valid['nama_produk'],
            'jenis_produk' => $valid['jenis_produk'],
            'harga_satuan' => $valid['harga_satuan'],
            'stok_gudang' => $valid['stok_awal'],
        ]);

        return response()->json(new ProdukResource($produk), 201);
    }

    public function produksi(ProduksiProdukRequest $request, string $kode_produk)
    {
        $produk = Produk::find($kode_produk);

        if (!$produk) {
            return ApiProblem::respond(
                404,
                'Produk tidak ditemukan',
                sprintf('Produk dengan kode %s tidak tersedia.', $kode_produk)
            );
        }

        $valid = $request->validated();

        DB::transaction(function () use ($produk, $valid) {
            $locked = Produk::where('kode_produk', $produk->kode_produk)->lockForUpdate()->first();
            $locked->stok_gudang += $valid['jumlah_produksi'];
            $locked->save();
        });

        return response()->json(new ProdukResource($produk->fresh()));
    }
}