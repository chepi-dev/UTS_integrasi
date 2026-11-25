<?php

namespace App\Http\Middleware;

use App\Http\ApiProblem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return ApiProblem::respond(
                403,
                'Akses ditolak',
                sprintf(
                    'Role pengguna (%s) tidak memiliki izin untuk mengakses resource ini.',
                    $user?->role ?? 'tidak diketahui'
                )
            );
        }

        return $next($request);
    }
}
