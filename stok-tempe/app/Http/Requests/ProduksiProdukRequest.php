<?php

namespace App\Http\Requests;

class ProduksiProdukRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'jumlah_produksi' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}