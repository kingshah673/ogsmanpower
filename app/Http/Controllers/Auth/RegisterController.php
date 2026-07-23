<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasCountryBasedJobs;
use App\Mail\SendCandidateMail;
use App\Models\Admin;
use App\Models\Candidate;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Admin\NewUserRegisteredNotification;
use App\Notifications\CandidateCreateApprovalPendingNotification;
use App\Notifications\CandidateCreateNotification;
use App\Notifications\CompanyCreateApprovalPendingNotification;
use App\Notifications\CompanyCreatedNotification;
use App\Notifications\AgencyCreateApprovalPendingNotification;
use App\Notifications\AgencyCreatedNotification;
use App\Services\Registration\EmployerRegistrationEmailService;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Agency;
use App\Models\Company;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    use HasCountryBasedJobs, RegistersUsers;

    /**
     * Redirect user after registration.
     */
    protected function redirectTo()
    {
        $user = auth()->user();

        if (! $user) {
            return route('website.home');
        }

        return user_post_auth_route($user);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show register form
     */
    public function showRegistrationForm()
    {
        $data['candidates'] = Candidate::count();
        $data['roles'] = Role::where('id', '!=', 1)->get();

        return view('frontend.auth.register', $data);
    }

    /**
     * Resolve the effective role string from the incoming request.
     */
    private function resolvedRole(array $data): string
    {
        return $this->resolveRegistrationRole($data);
    }

    /**
     * Map registration type/role inputs to a canonical portal role.
     */
    private function resolveRegistrationRole(array $data): string
    {
        $raw = request()->input('type')
            ?: ($data['type'] ?? null)
            ?: request()->query('type')
            ?: request()->input('role')
            ?: ($data['role'] ?? null);

        $meta = registrationTypeMeta(is_string($raw) ? $raw : null);

        if ($meta['role'] !== '') {
            return $meta['role'];
        }

        $role = strtolower(trim((string) $raw));

        return match ($role) {
            'seeker', 'candidate', 'job_seeker' => 'candidate',
            'employer', 'company' => 'company',
            'agency' => 'agency',
            'broker', 'middleman', 'demand_partner' => 'broker',
            'hr', 'agent', 'hr_referral' => $this->resolveHrAgentRole($role === 'hr' ? 'hr' : 'agent', $data),
            default => '',
        };
    }

    private function registrationTypeKey(array $data): string
    {
        $raw = request()->input('type')
            ?: ($data['type'] ?? null)
            ?: request()->query('type')
            ?: '';

        $meta = registrationTypeMeta(is_string($raw) ? $raw : null);

        return $meta['type'] !== '' ? $meta['type'] : strtolower(trim((string) $raw));
    }

    /**
     * HR / agent registration may map to agency or agent depending on selection.
     */
    private function resolveHrAgentRole(string $role, array $data): string
    {
        $hrResource = $data['hr_resource'] ?? request()->input('hr_resource');

        if (! empty($hrResource)) {
            $selectedRole = Role::find($hrResource);

            if ($selectedRole) {
                $roleName = strtolower(trim($selectedRole->name));
                $agencyTypes = [
                    'recruitment agency',
                    'third party workforce supply',
                    'hr consultancy',
                    'third party contracting small establishment',
                    'domestic worker istaqdam offices',
                    'agency',
                ];

                return in_array($roleName, $agencyTypes, true) ? 'agency' : 'agent';
            }
        }

        return $role === 'agency' ? 'agency' : 'agent';
    }

    private function assignPortalSpatieRole(User $user, string $finalRole): void
    {
        $spatieName = match ($finalRole) {
            'candidate' => 'Seeker',
            'company', 'agency', 'agent', 'broker' => 'Employer',
            default => null,
        };

        if (! $spatieName) {
            return;
        }

        $role = Role::where('name', $spatieName)
            ->where('guard_name', 'user')
            ->first();

        if ($role) {
            $user->assignRole($role);
        }
    }

    /**
     * Detect whether this request is a company registration.
     */
    private function isCompanyRegistration(array $data): bool
    {
        return registrationFormRequirements($this->registrationTypeKey($data))['company_docs'];
    }

    /**
     * Detect whether this request is an agency registration.
     */
    private function isAgencyRegistration(array $data): bool
    {
        return registrationFormRequirements($this->registrationTypeKey($data))['agency_license'];
    }

    /**
     * Validate register request
     */
    protected function validator(array $data)
    {
        $isCompany = $this->isCompanyRegistration($data);
        $isAgency  = !$isCompany && $this->isAgencyRegistration($data);

        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required', 'string', 'email', 'max:255', 'unique:users,email',
            ],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'role'        => ['nullable', 'string'],
            'type'        => ['nullable', 'string'],
            'hr_resource' => ['nullable'],
            'whatsapp'    => ['nullable', 'string', 'max:30'],
            'g-recaptcha-response' => config('captcha.active') ? 'required|captcha' : '',
            'registration_number'  => ['nullable', 'string', 'max:100'],
            'license_number'       => ['nullable', 'string', 'max:50'],
        ];

        // Corporate domain check — only for company registrations when enabled by admin.
        // Trade license / CR uploads happen after OTP on /company/verify-documents.
        if ($isCompany) {
            $rules['registration_number'] = ['required', 'string', 'max:100'];

            if (EmployerRegistrationEmailService::corporateEmailRequired()) {
                $rules['email'][] = function ($attribute, $value, $fail) {
                    $message = EmployerRegistrationEmailService::validationMessage($value);
                    if ($message) {
                        $fail($message);
                    }
                };
            }
        }

        // MPD / BEOE license required for agency registrations
        if ($isAgency) {
            // Format: 2-50 alphanumeric chars, optional dashes / slashes (e.g. 2978, MPD-2978, 2978/2024)
            $rules['license_number'] = [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9\-\/\.\s]{0,48}[A-Za-z0-9]$|^[A-Za-z0-9]{1}$/',
            ];
        }

        return Validator::make(
            $data,
            $rules,
            [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha'  => 'Captcha error! Try again later or contact admin.',
                'registration_number.required'  => 'Company registration number is required.',
                'license_number.required'       => 'MPD / agency license number is required for agency registration.',
                'license_number.regex'          => 'License number format is invalid (e.g. 2978, MPD-2978, 2978/2024).',
            ]
        )->after(function ($validator) use ($data) {
            if ($this->resolveRegistrationRole($data) === '') {
                $validator->errors()->add('type', 'Please choose your registration role from the sign-up options.');
            }
        });
    }

    /**
     * Create user after registration
     */
    protected function create(array $data)
    {
    /**
     * Generate username
     */
    $newUsername = Str::slug($data['name']);
    $oldUserName = User::where('username', $newUsername)->first();

    $username = $oldUserName
        ? $newUsername . '_' . Str::random(5)
        : $newUsername;

    $finalRole = $this->resolveRegistrationRole($data);

    if ($finalRole === '') {
        $finalRole = 'candidate';
    }

    $signupType = $this->registrationTypeKey($data) ?: null;

    /**
     * Create User
     */
    $user = User::create([
        'role'        => $finalRole,
        'signup_type' => $signupType,
        'name'        => $data['name'],
        'username'    => $username,
        'email'       => $data['email'],
        'whatsapp'    => $data['whatsapp'] ?? null,
        'password'    => Hash::make($data['password']),
    ]);

    $this->assignPortalSpatieRole($user, $finalRole);

    // For company registrations, seed the Company record with the registration number
    if ($finalRole === 'company' && !empty($data['registration_number'])) {
        Company::firstOrCreate(
            ['user_id' => $user->id],
            ['registration_number' => $data['registration_number']]
        );
    }

    // For agency registrations, seed the Agency record with the MPD license number
    if ($finalRole === 'agency' && !empty($data['license_number'])) {
        Agency::firstOrCreate(
            ['user_id' => $user->id],
            ['license_number' => $data['license_number']]
        );
    }

    // Company verification docs are uploaded after OTP at /company/verify-documents.
    // Default required document types are assigned in Company::created.

    // Link agent to inviting agency if a valid invite token is in the session or request
    if ($finalRole === 'agent') {
        $inviteToken = session('agent_invite_token') ?? request()->input('invite_token');
        if ($inviteToken) {
            $invite = \App\Models\AgentInvite::where('token', $inviteToken)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();
            if ($invite) {
                $user->update(['agency_id' => $invite->agency_user_id]);
                $invite->update(['accepted_at' => now()]);
                session()->forget('agent_invite_token');
            }
        }
    }

    return $user;
    }
}