<style>
/* ===== EMPLOYER SIDEBAR — matches seeker settings theme ===== */
.main-sidebar {
    background: #0f172a !important;
    border-right: 1px solid rgba(255,255,255,0.06);
    box-shadow: 2px 0 16px rgba(0,0,0,0.2);
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
}

.main-sidebar::-webkit-scrollbar { width: 5px; }
.main-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
}

.brand-link {
    display: flex !important;
    align-items: center;
    gap: 10px;
    color: #f8fafc !important;
    font-weight: 600;
    font-size: 14px;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    text-decoration: none !important;
}

.brand-link img {
    width: 32px;
    height: 32px;
    object-fit: contain;
    border-radius: 6px;
    background: #fff;
    padding: 2px;
}

.brand-text {
    color: #f1f5f9 !important;
    font-weight: 600 !important;
}

.profile_section {
    padding: 16px 14px 14px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.profile-image {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(37, 99, 235, 0.5);
    margin-bottom: 8px;
}

.profile_section .profile-name {
    color: #f1f5f9 !important;
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 4px;
}

.profile_section .profile-meta {
    color: #94a3b8 !important;
    font-size: 11px;
    margin: 2px 0;
    word-break: break-all;
}

.profile_section .profile-meta i {
    width: 14px;
    color: #64748b;
}

.profile_section h6 {
    color: #cbd5e1 !important;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 12px 0 6px;
    text-align: left;
    padding: 0 4px;
}

.profile_section .progress {
    height: 6px;
    background: rgba(255,255,255,0.08) !important;
    border-radius: 999px;
    overflow: hidden;
}

.profile_section .progress-bar {
    background: linear-gradient(90deg, #2563eb, #3b82f6) !important;
    border-radius: 999px;
    font-size: 0;
    line-height: 6px;
}

.profile_section .progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    color: #94a3b8;
    margin-top: 4px;
    padding: 0 2px;
}

.nav-sidebar .nav-link {
    position: relative;
    color: #cbd5e1 !important;
    font-size: 13px;
    border-radius: 8px;
    margin: 2px 8px;
    padding: 9px 12px;
    transition: background 0.15s, color 0.15s;
}

.nav-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.06) !important;
    color: #fff !important;
}

.nav-sidebar .nav-link.active {
    background: rgba(37, 99, 235, 0.15) !important;
    color: #fff !important;
    font-weight: 500;
}

.nav-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 7px;
    bottom: 7px;
    width: 3px;
    background: #2563eb;
    border-radius: 0 3px 3px 0;
}

.nav-sidebar .nav-link i {
    color: #64748b !important;
    font-size: 16px;
    margin-right: 2px;
}

.nav-sidebar .nav-link.active i {
    color: #60a5fa !important;
}

.sidebar-menu {
    list-style: none;
    padding: 8px 0 16px;
    margin: 0;
    border-top: 1px solid rgba(255,255,255,0.06);
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #f87171 !important;
    font-size: 13px;
    border-radius: 8px;
    margin: 4px 8px;
    padding: 9px 12px;
    text-decoration: none !important;
}

.nav-sidebar .nav-item > a.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.sidebar-menu a:hover {
    background: rgba(248, 113, 113, 0.1) !important;
}
</style>
<aside id="sidebar" class="main-sidebar sidebar-dark-primary elevation-4">
    @php
        $logoUrl = $setting->favicon_image_url ?? 'logo.png';
        if ($logoUrl && ! str_starts_with($logoUrl, 'http')) {
            $logoUrl = asset($logoUrl);
        }
        $companyUser = auth()->user()->company;
        $phoneDisplay = auth()->user()->whatsapp
            ?: optional(auth()->user()->contactInfo)->phone
            ?: auth()->user()->phone;
        $companyCompletion = $companyUser->profile_completion ? 100 : (
            collect([
                filled($companyUser->logo),
                filled($companyUser->name ?? auth()->user()->name),
                filled($companyUser->bio),
                filled($companyUser->industry_type_id),
                filled($companyUser->organization_type_id),
                optional(auth()->user()->contactInfo)->phone || optional(auth()->user()->contactInfo)->email,
            ])->filter()->count() * (100 / 6)
        );
    @endphp
    <a href="{{ route('company.dashboard') }}" class="brand-link">
        <img src="{{ $logoUrl }}" alt="{{ __('logo') }}">
        <span class="brand-text">{{ config('app.name') }}</span>
    </a>

    <div class="profile_section">
        <img src="{{ $companyUser->logo_url }}" alt="" class="profile-image">
        <p class="profile-name">{{ auth()->user()->name }}</p>
        <p class="profile-meta"><i class="fas fa-envelope"></i> {{ auth()->user()->email }}</p>
        @if($phoneDisplay)
            <p class="profile-meta"><i class="fas fa-phone"></i> {{ $phoneDisplay }}</p>
        @endif

        <h6>{{ __('Profile Status') }}</h6>
        <div class="progress">
            <div class="progress-bar" role="progressbar"
                 style="width: {{ min(100, $companyCompletion) }}%;"
                 aria-valuenow="{{ $companyCompletion }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="progress-label">
            <span>{{ __('Complete') }}</span>
            <span>{{ number_format($companyCompletion, 0) }}%</span>
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-nav-wrapper">
            <nav class="sidebar-main-nav mt-1">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" role="menu">
                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.dashboard')" route="company.dashboard" icon="ph-squares-four" path="company.dashboard" plus_icon="">
                        {{ __('Dashboard') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.myjob')" route="company.myjob" icon="ph-suitcase-simple" path="company.myjob" plus_icon="">
                        {{ __('my_jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.applicants', 'company.job.application')" route="company.applicants" icon="ph-user-list" path="company.applicants" plus_icon="">
                        Applicants
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.interviews')" route="company.interviews" icon="ph-calendar-check" path="company.interviews" plus_icon="">
                        Interviews
                    </x-admin.sidebar-list>

                    @if (!$setting->edited_job_auto_approved)
                        <x-admin.sidebar-list :linkActive="request()->routeIs('company.pending.edited.jobs')" route="company.pending.edited.jobs" icon="ph-clock" path="company.pending.edited.jobs" plus_icon="">
                            {{ __('pending_edited_jobs') }}
                        </x-admin.sidebar-list>
                    @endif

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.job.create')" route="company.job.create" icon="ph-plus-circle" path="company.job.create" plus_icon="">
                        {{ __('post_a_job') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.pipeline')" route="company.pipeline" icon="ph-users-three" path="company.pipeline" plus_icon="">
                        {{ __('Candidate Pipeline') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.visa-processing.*')" route="company.visa-processing.index" icon="ph-passport" path="company.visa-processing.index" plus_icon="">
                        Visa Processing
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.nominated-workers.*')" route="company.nominated-workers.index" icon="ph-users-four" path="company.nominated-workers.index" plus_icon="">
                        Nominated Workers
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.bookmark')" route="company.bookmark" icon="ph-bookmark-simple" path="company.bookmark" plus_icon="">
                        {{ __('saved_candidate') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.questions.manage')" route="company.questions.manage" icon="ph-question" path="company.questions.manage" plus_icon="">
                        {{ __('custom_questions') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.contracts.*')" route="company.contracts.index" icon="ph-file-doc" path="company.contracts.index" plus_icon="">
                        {{ __('Contracts') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.plan')" route="company.plan" icon="ph-crown" path="company.plan" plus_icon="">
                        {{ __('plans_billing') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.verify.documents.index')" route="company.verify.documents.index" icon="ph-seal-check" path="company.verify.documents.index" plus_icon="">
                        {{ __('verify_account') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.candidate-status')" route="company.candidate-status" icon="ph-user-check" path="company.candidate-status" plus_icon="">
                        {{ __('Status of Workers') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list
                        :linkActive="request()->routeIs('website.employe.details') && request()->route('user')?->username === auth()->user()?->username"
                        route="website.employe.details"
                        :routeParams="[auth()->user()->username]"
                        icon="ph-buildings"
                        path="website.employe.details"
                        plus_icon="">
                        {{ __('my_profile') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('company.setting')" route="company.setting" icon="ph-gear" path="company.setting" plus_icon="">
                        {{ __('settings') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('website.home')" route="website.home" icon="ph-globe" path="website.home" plus_icon="">
                        {{ __('View Website') }}
                    </x-admin.sidebar-list>
                </ul>

                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ph-sign-out"></i>
                            {{ __('log_out') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</aside>
