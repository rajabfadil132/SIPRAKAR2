<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user()) {
            return redirect()->route('login')->with('error', 'Silakan login untuk melanjutkan.');
        }

        return $next($request);
    }
}
