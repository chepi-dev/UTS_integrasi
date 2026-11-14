<?php

namespace App\Http\Requests;

class AmbilKonsinyasiRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'kode_pedagang' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.kode_produk' => ['required', 'string', 'distinct'],
            'items.*.jumlah_dibawa' => ['required', 'integer', 'min:1'],
        ];
    }
}