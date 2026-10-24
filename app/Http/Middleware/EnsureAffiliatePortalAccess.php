<?php

namespace App\Http\Middleware;

use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;

class EnsureAffiliatePortalAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(AffiliateService::canAccessPortal($request->user()), 403, 'Akun Anda tidak memiliki akses Portal Afiliasi.');

        return $next($request);
    }
}
