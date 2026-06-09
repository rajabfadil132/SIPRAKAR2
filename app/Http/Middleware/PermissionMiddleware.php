<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login untuk melanjutkan.');
        }

        abort_unless($user->hasPermission($permission), 403, 'Anda tidak memiliki hak akses untuk fitur ini.');

        return $next($request);
    }
}
