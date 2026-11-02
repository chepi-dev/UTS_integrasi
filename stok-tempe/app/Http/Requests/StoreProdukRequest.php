<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kategori_id' => ['required', 'exists:kategori_produk,id'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'in:papan,potong,biji,kg'],
            'harga_satuan' => ['required', 'integer', 'min:0'],
            'stok_awal' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
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