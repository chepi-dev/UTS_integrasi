<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_penyesuaian' => ['required', 'string', 'in:masuk_produksi,penjualan,rusak_basi,koreksi_opname'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'type' => url('/api/v1/problems/validation'),
                'title' => 'Validasi gagal',
                'status' => 422,
                'detail' => 'Validasi permintaan gagal',
                'errors' => $validator->errors(),
            ], 422, ['Content-Type' => 'application/problem+json'])
        );
    }
}