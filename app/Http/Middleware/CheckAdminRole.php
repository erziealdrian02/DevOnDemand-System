<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        // role_id = 1 berarti admin
        if (Auth::check() && Auth::user()->role_id === 1) {
            return $next($request);
        }

        abort(403, 'Unauthorized.');
    }
}

