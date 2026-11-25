<?php

namespace App\Http\Middleware;

use App\Http\ApiProblem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyPemilikToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return ApiProblem::respond(
                401,
                'Tidak terautentikasi',
                'Token autentikasi tidak disertakan pada header Authorization: Bearer.'
            );
        }

        $stokBaseUrl = rtrim((string) env('STOK_SERVICE_URL', 'http://127.0.0.1:8000'), '/');

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(5)
                ->get($stokBaseUrl.'/api/v1/auth/me');
        } catch (Throwable $e) {
            return ApiProblem::respond(
                503,
                'Layanan autentikasi tidak terjangkau',
                'Gagal menghubungi layanan stok-tempe untuk verifikasi token.'
            );
        }

        if ($response->status() === 401 || ! $response->successful()) {
            return ApiProblem::respond(
                401,
                'Tidak terautentikasi',
                'Token autentikasi tidak valid atau sudah kedaluwarsa.'
            );
        }

        $user = $response->json();

        if (($user['role'] ?? null) !== 'pemilik') {
            return ApiProblem::respond(
                403,
                'Akses ditolak',
                sprintf(
                    'Role pengguna (%s) tidak memiliki izin untuk membuka buku pembukuan.',
                    $user['role'] ?? 'tidak diketahui'
                )
            );
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}