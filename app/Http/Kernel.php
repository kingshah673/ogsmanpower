<?php

namespace App\Http;

use App\Http\Middleware\AutoSetCountryLanguageCurrency;
use App\Http\Middleware\EmailVerifiedMiddleware;
use App\Http\Middleware\HasPlanMiddleware;
use App\Http\Middleware\UserActiveMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\SecurityHeaders::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // \App\Http\Middleware\PreventCache::class,
    ];

    /**
     * Route middleware groups
     */
    protected $middlewareGroups = [

        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LanguageManager::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Route middleware
     */
    protected $routeMiddleware = [

        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        'verified' => EmailVerifiedMiddleware::class,
        'otp.verified' => \App\Http\Middleware\VerifyOTP::class,
        'profile.approved' => \App\Http\Middleware\VerifyProfile::class,
        'user_active' => UserActiveMiddleware::class,

        'set_lang' => \Modules\Language\Http\Middleware\SetLangMiddleware::class,

        // ✅ FIXED SPATIE PERMISSION MIDDLEWARE
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

        'candidate' => \App\Http\Middleware\CandidateMiddleware::class,
        'company' => \App\Http\Middleware\CompanyMiddleware::class,
        'company.profile' => \App\Http\Middleware\CompanyProfileCompletion::class,
        'company.documents' => \App\Http\Middleware\CompanyDocumentVerification::class,
        'agency' => \App\Http\Middleware\AgencyMiddleware::class,
        'agency.profile' => \App\Http\Middleware\AgencyProfileCompletion::class,
        'agency.documents' => \App\Http\Middleware\AgencyDocumentVerification::class,
        'agent' => \App\Http\Middleware\AgentMiddleware::class,
        'broker' => \App\Http\Middleware\BrokerMiddleware::class,

        'check_mode' => \App\Http\Middleware\CheckForAppMode::class,
        'access_limitation' => \App\Http\Middleware\AccessLimitation::class,
        'auto_set_country_language_currency' => AutoSetCountryLanguageCurrency::class,
        'has_plan' => HasPlanMiddleware::class,

        'api_company' => \App\Http\Middleware\Api\CompanyApiMiddleware::class,
        'api_agency' => \App\Http\Middleware\Api\AgencyApiMiddleware::class,
        'api_has_plan' => \App\Http\Middleware\Api\HasPlanApiMiddleware::class,

        'prevent_cache' => \App\Http\Middleware\PreventCache::class,
        'verify.whatsapp'=> \App\Http\Middleware\VerifyWhatsAppSignature::class,
        'feature' => \App\Http\Middleware\CheckPlanFeature::class,
        'portal_user_admin_guard' => \App\Http\Middleware\RedirectPortalUsersFromAdmin::class,
    ];
}