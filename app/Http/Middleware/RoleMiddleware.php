<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Cek apakah user memiliki salah satu role yang diizinkan.
     *
     * Penggunaan di route: ->middleware('role:admin,noc')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Cek custom role (string) ATAU Spatie roles
        if (in_array($user->role, $roles) || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles))) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
