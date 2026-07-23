<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'agent') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Agent / Facilitator access required.'], 403);
            }

            return redirect()->route('login')->with('error', 'Agent / Facilitator access required.');
        }

        if (! $user->agency_id && ! $request->routeIs('agent.setting', 'agent.setting.update')) {
            return redirect()->route('agent.setting')
                ->with('warning', 'Link your account to a parent agency to use the Agent / Facilitator portal.');
        }

        if ((int) $user->status === 0) {
            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Your Agent / Facilitator account has been suspended. Contact your agency or admin.');
        }

        return $next($request);
    }
}
