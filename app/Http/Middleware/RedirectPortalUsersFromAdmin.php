<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectPortalUsersFromAdmin
{
    /**
     * Portal users (seekers, employers, agents) must not browse the admin panel.
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth('user')->check() && ! auth('admin')->check()) {
            return redirect(user_home_route());
        }

        return $next($request);
    }
}
