<?php

namespace App\Http\Middleware;

use App\Services\Company\CompanyDocumentVerificationService;
use Closure;
use Illuminate\Http\Request;

class CompanyDocumentVerification
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('company.verify.documents.*')) {
            return $next($request);
        }

        $redirect = CompanyDocumentVerificationService::redirectIfBlocked(currentCompany());

        if ($redirect) {
            return $redirect;
        }

        return $next($request);
    }
}
