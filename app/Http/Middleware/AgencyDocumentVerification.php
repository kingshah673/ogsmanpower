<?php

namespace App\Http\Middleware;

use App\Services\Agency\AgencyDocumentVerificationService;
use Closure;
use Illuminate\Http\Request;

class AgencyDocumentVerification
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('agency.verify.documents.*')) {
            return $next($request);
        }

        $redirect = AgencyDocumentVerificationService::redirectIfBlocked(currentAgency());

        if ($redirect) {
            return $redirect;
        }

        return $next($request);
    }
}
