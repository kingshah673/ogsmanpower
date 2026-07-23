<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class AgencyApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->role == 'agency') {
            return $next($request);
        }

        abort(401, 'Unathenticated');
    }
}
