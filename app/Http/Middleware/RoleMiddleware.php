<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login untuk melanjutkan.');
        }

        abort_if($user->status !== 'active', 403, 'Akun Anda tidak aktif.');

        $allowed = collect($roles)->map(fn ($role) => Str::slug(Str::lower($role)))->all();
        abort_unless(in_array($user->roleKey(), $allowed, true), 403, 'Role Anda tidak diizinkan mengakses fitur ini.');

        return $next($request);
    }
}
