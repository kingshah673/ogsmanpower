<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BrokerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = authUser();

        if ($user && $user->role === 'broker') {
            return $next($request);
        }

        if ($user?->role === 'candidate') {
            return redirect()->route('candidate.dashboard');
        }
        if ($user?->role === 'company') {
            return redirect()->route('company.dashboard');
        }
        if ($user?->role === 'agency') {
            return redirect()->route('agency.dashboard');
        }
        if ($user?->role === 'agent') {
            return redirect()->route('agent.dashboard');
        }

        return redirect()->route('login');
    }
}
