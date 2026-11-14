<?php

namespace App\Http\Requests;

class StorePedagangRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'nama_pedagang' => ['required', 'string', 'max:100'],
            'no_telepon' => ['required', 'string', 'max:20'],
            'wilayah_jualan' => ['nullable', 'string', 'max:100'],
        ];
    }
}