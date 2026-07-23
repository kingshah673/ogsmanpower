<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        $feature
    ) {

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | NOT LOGGED IN
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return redirect()
                ->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | NO ACTIVE PLAN
        |--------------------------------------------------------------------------
        */

        if (!$user->activePlan) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'No active subscription plan found'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | FEATURE CHECK
        |--------------------------------------------------------------------------
        */

        if (!$user->canUseFeature($feature)) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Your plan limit has been exceeded'
                );
        }

        return $next($request);
    }
}