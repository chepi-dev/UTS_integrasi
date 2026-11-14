<?php

namespace App\Http\Requests;

class StoreProdukRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'nama_produk' => ['required', 'string', 'max:100'],
            'jenis_produk' => ['required', 'string', 'in:tahu,tempe'],
            'harga_satuan' => ['required', 'integer', 'min:0'],
            'stok_awal' => ['required', 'integer', 'min:0'],
        ];
    }
}