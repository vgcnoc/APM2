<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyModule
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if ($user) {
            // Super Admin Holding selalu memiliki hak akses tak terbatas
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return $next($request);
            }

            // Periksa perusahaan user mitra
            $company = $user->company;
            if ($company && !$company->is_holding && !$company->hasModule($module)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => "Akses modul '{$module}' belum diaktifkan oleh Administrator Holding untuk perusahaan Anda.",
                    ], 403);
                }

                abort(403, "Akses modul '{$module}' belum diaktifkan oleh Administrator Holding untuk perusahaan Anda.");
            }
        }

        return $next($request);
    }
}

