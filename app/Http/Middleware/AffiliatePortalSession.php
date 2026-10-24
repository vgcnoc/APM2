<?php

namespace App\Http\Middleware;

use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;

class AffiliatePortalSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! AffiliateService::usesAffiliatePortal($request)) {
            return $next($request);
        }

        if ($request->routeIs('affiliate.*', 'logout')) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->route('affiliate.dashboard');
        }

        abort(403, 'Sesi Afiliasi hanya dapat mengakses booking pribadi dan jaringan downline sendiri.');
    }
}
