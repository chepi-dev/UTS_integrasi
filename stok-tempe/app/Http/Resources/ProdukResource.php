<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_produk' => $this->kode_produk,
            'nama_produk' => $this->nama_produk,
            'kategori' => [
                'id' => $this->kategori?->id,
                'nama' => $this->kategori?->nama_kategori,
                'daya_tahan_hari' => $this->kategori?->daya_tahan_hari,
            ],
            'satuan' => $this->satuan,
            'harga_satuan' => $this->harga_satuan,
            'jumlah_stok' => $this->jumlah_stok,
            'stok_minimum' => $this->stok_minimum,
            'status_stok' => $this->statusStok,
            'status' => $this->status,
            'terakhir_diperbarui' => $this->terakhir_diperbarui?->toISOString(),
        ];
    }
}