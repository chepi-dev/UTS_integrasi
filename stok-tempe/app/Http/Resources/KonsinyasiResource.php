<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KonsinyasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_manifest' => $this->kode_manifest,
            'pedagang' => [
                'kode_pedagang' => $this->pedagang->kode_pedagang,
                'nama_pedagang' => $this->pedagang->nama_pedagang,
            ],
            'tanggal_konsinyasi' => $this->tanggal_konsinyasi->format('Y-m-d'),
            'status' => $this->status,
            'waktu_ambil' => $this->waktu_ambil?->toISOString(),
            'waktu_setor' => $this->waktu_setor?->toISOString(),
            'total_tagihan_setoran' => $this->total_tagihan_setoran,
            'total_kerugian_waste' => $this->total_kerugian_waste,
            'items' => $this->details->map(fn ($detail) => [
                'kode_produk' => $detail->kode_produk,
                'nama_produk' => $detail->produk->nama_produk ?? '-',
                'harga_satuan_snapshot' => $detail->harga_satuan_snapshot,
                'jumlah_dibawa' => $detail->jumlah_dibawa,
                'jumlah_terjual' => $detail->jumlah_terjual,
                'jumlah_retur_layak' => $detail->jumlah_retur_layak,
                'jumlah_rusak' => $detail->jumlah_rusak,
            ])->values(),
        ];
    }
}