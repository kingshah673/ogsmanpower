<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgencyProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        $agency = currentAgency();

        if (!$agency) {
            return redirect()->route('agency.setting');
        }

        if (!$agency->profile_completion) {

            if (!$request->is('agency/account-progress*')) {
                return redirect()->route('agency.account-progress');
            }

        }

        return $next($request);
    }
}