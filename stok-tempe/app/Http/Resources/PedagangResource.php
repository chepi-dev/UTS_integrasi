<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedagangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode_pedagang' => $this->kode_pedagang,
            'nama_pedagang' => $this->nama_pedagang,
            'no_telepon' => $this->no_telepon,
            'wilayah_jualan' => $this->wilayah_jualan,
            'aktif' => $this->aktif,
        ];
    }
}