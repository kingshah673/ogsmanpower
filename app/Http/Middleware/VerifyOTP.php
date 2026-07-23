<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;

class VerifyOTP
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isAdminRequest($request)) {
            return $this->handleAdmin($request, $next);
        }

        return $this->handlePortalUser($request, $next);
    }

    protected function isAdminRequest(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*');
    }

    protected function handleAdmin(Request $request, Closure $next)
    {
        $admin = auth('admin')->user();

        if (! $admin || ($admin->is_otp_verified ?? true)) {
            return $next($request);
        }

        return redirect()->route('otp.verify')->withErrors([
            'otp' => 'Please verify your email with OTP.',
        ]);
    }

    protected function handlePortalUser(Request $request, Closure $next)
    {
        $user = auth('user')->user();

        if (! $user || ($user->is_otp_verified ?? false)) {
            return $next($request);
        }

        $spatieName = match (true) {
            $user->role === 'candidate' => 'Seeker',
            in_array($user->role, ['company', 'agency', 'agent'], true) => 'Employer',
            default => null,
        };

        $hasActiveMethods = false;

        if ($spatieName) {
            $role = Role::where('name', $spatieName)
                ->where('guard_name', 'user')
                ->first();

            if ($role) {
                $hasActiveMethods = $role->activeOtpMethods()->exists();
            }
        }

        if (! $hasActiveMethods) {
            $user->is_otp_verified = true;
            $user->save();

            return $next($request);
        }

        return redirect()->route('otp.verify')->withErrors([
            'otp' => 'Please verify your email with OTP.',
        ]);
    }
}
