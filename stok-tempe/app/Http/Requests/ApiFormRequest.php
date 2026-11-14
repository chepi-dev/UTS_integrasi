<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'type' => 'https://localhost:8000/api/v1/problems/validation',
                'title' => 'Validasi gagal',
                'status' => 422,
                'detail' => 'Data permintaan tidak memenuhi aturan validasi.',
                'errors' => $validator->errors(),
            ], 422, ['Content-Type' => 'application/problem+json'])
        );
    }
}