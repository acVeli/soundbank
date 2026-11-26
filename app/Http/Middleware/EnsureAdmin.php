<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_admin !== true) {
            abort(403, 'Accès réservé à l’administration.');
        }

        return $next($request);
    }
}
