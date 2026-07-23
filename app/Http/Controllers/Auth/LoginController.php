<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasCountryBasedJobs;
use App\Models\Candidate;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers, HasCountryBasedJobs;

    /**
     * Show login form
     */
    public function showLoginForm(Request $request)
    {
        $data['candidates'] = Candidate::count();
        $userType = $request->query('userType', 'candidate');

        return view('frontend.auth.login', $data, compact('userType'));
    }

    /**
     * After login redirect handler.
     */
    protected function authenticated(Request $request, $user)
    {
        $request->session()->regenerate();

        // Companies and agencies require admin approval and profile completion before accessing the dashboard.
        // Candidates and agents do not have is_profile_verified or profile_completion columns.
        if (in_array($user->role, ['company', 'agency'])) {
            $profile = $this->getUserProfile($user);

            if (!$profile) {
                return redirect()->route($this->getRouteByRole($user, 'setting'))
                    ->with('error', 'Profile not found. Please complete your profile.');
            }

            if ($user->role === 'company') {
                $redirect = CompanyDocumentVerificationService::redirectIfBlocked($profile);
                if ($redirect) {
                    return $redirect;
                }
            } elseif ((int) ($profile->is_profile_verified ?? 0) !== 1) {
                $this->guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Your account is not approved yet. Please wait for admin approval.');
            }

            if ((int) ($profile->profile_completion ?? 0) !== 1) {
                return redirect()->route($this->getRouteByRole($user, 'setting'))
                    ->with('warning', 'Please complete your profile first.');
            }
        }

        return redirect()->route($this->getRouteByRole($user, 'dashboard'));
    }

    /**
     * Get profile based on role (SAFE)
     */
    private function getUserProfile($user)
    {
        try {
            switch ($user->role) {
                case 'agency':
                    return $user->agency;
                case 'company':
                    return $user->company;
                case 'agent':
                    return $user->agent;
                case 'broker':
                    return $user->broker;
                case 'candidate':
                    return $user->candidate;
                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ✅ Dynamic route generator
     */
   private function getRouteByRole($user, $type)
{
    return match ($user->role) {

        'agency' => match ($type) {
            'dashboard' => 'agency.dashboard',
            'setting'   => 'agency.setting',
            'progress'  => 'agency.setting',
            default     => 'agency.dashboard',
        },

        'company' => match ($type) {
            'dashboard' => 'company.dashboard',
            'setting'   => 'company.setting',
            'progress'  => 'company.setting',
            default     => 'company.dashboard',
        },

        'agent' => match ($type) {
            'dashboard' => 'agent.dashboard',
            'setting'   => 'agent.setting',
            'progress'  => 'agent.setting',
            default     => 'agent.dashboard',
        },

        'broker' => match ($type) {
            'dashboard' => 'broker.dashboard',
            'setting'   => 'broker.setting',
            'progress'  => 'broker.setting',
            default     => 'broker.dashboard',
        },

        'candidate' => match ($type) {
            'dashboard' => 'candidate.dashboard',
            'setting'   => 'candidate.setting',
            'progress'  => 'candidate.setting',
            default     => 'candidate.dashboard',
        },

        default => 'website.home',
    };
}

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'g-recaptcha-response' => config('captcha.active') ? 'required|captcha' : '',
        ], [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ]);

        // 🔒 Login attempt limit
        if (
            method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)
        ) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // ✅ Attempt login
        if ($this->attemptLogin($request)) {

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Guard
     */
    protected function guard()
    {
        return Auth::guard('user');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $sessionKeys = ['current_currency', 'current_lang', 'selected_country'];

        $sessionData = [];

        foreach ($sessionKeys as $key) {
            $sessionData[$key] = session($key);
        }

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        foreach ($sessionData as $key => $value) {
            session([$key => $value]);
        }

        return redirect()->route('website.home');
    }
}