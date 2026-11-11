<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BatasiPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        if (! in_array($request->user()?->role, $peran, true)) {
            abort(403, 'Anda tidak berhak membuka halaman ini.');
        }

        return $next($request);
    }
}
