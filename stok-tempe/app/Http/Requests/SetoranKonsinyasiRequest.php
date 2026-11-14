<?php

namespace App\Http\Requests;

class SetoranKonsinyasiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.kode_produk' => ['required', 'string', 'distinct'],
            'items.*.jumlah_terjual' => ['required', 'integer', 'min:0'],
            'items.*.jumlah_retur_layak' => ['required', 'integer', 'min:0'],
            'items.*.jumlah_rusak' => ['required', 'integer', 'min:0'],
        ];
    }
}