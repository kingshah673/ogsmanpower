<style>
/* ===== SEEKER SIDEBAR — matches settings theme ===== */
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
        $candidateUser = auth()->user()->candidate;
        $phoneDisplay = auth()->user()->whatsapp
            ?: optional(auth()->user()->contactInfo)->phone
            ?: auth()->user()->phone;
    @endphp
    <a href="{{ route('candidate.dashboard') }}" class="brand-link">
        <img src="{{ $logoUrl }}" alt="{{ __('logo') }}">
        <span class="brand-text">{{ config('app.name') }}</span>
    </a>

    <div class="profile_section">
        <img src="{{ asset($candidateUser->photo) }}" alt="" class="profile-image">
        <p class="profile-name">{{ auth()->user()->name }}</p>
        <p class="profile-meta"><i class="fas fa-envelope"></i> {{ auth()->user()->email }}</p>
        @if($phoneDisplay)
            <p class="profile-meta"><i class="fas fa-phone"></i> {{ $phoneDisplay }}</p>
        @endif

        <h6>{{ __('Profile Status') }}</h6>
        <div class="progress">
            <div class="progress-bar" role="progressbar"
                 style="width: {{ min(100, $completionPercentage) }}%;"
                 aria-valuenow="{{ $completionPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="progress-label">
            <span>{{ __('Complete') }}</span>
            <span>{{ number_format($completionPercentage, 0) }}%</span>
        </div>

        <x-website.candidate.profile-completion-hints :compact="true" />
    </div>

    <div class="sidebar">
        <div class="sidebar-nav-wrapper">
            <nav class="sidebar-main-nav mt-1">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" role="menu">
                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.dashboard')" route="candidate.dashboard" icon="ph-squares-four" path="candidate.dashboard" plus_icon="">
                        {{ __('Dashboard') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('website.job') || request()->routeIs('website.job.*')" route="website.job" icon="ph-magnifying-glass" path="website.job" plus_icon="">
                        {{ __('Jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.view.cv')" route="candidate.view.cv" icon="ph-file-text" path="candidate.view.cv" plus_icon="">
                        {{ __('Bilingual CV') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.appliedjob')" route="candidate.appliedjob" icon="ph-suitcase-simple" path="candidate.appliedjob" plus_icon="">
                        {{ __('applied_jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.bookmark')" route="candidate.bookmark" icon="ph-bookmark-simple" path="candidate.bookmark" plus_icon="">
                        {{ __('favorite_jobs') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.job.alerts')" route="candidate.job.alerts" icon="ph-bell-ringing" path="candidate.job.alerts" plus_icon="">
                        {{ __('job_alert') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('contracts.*')" route="contracts.index" icon="ph-file-doc" path="contracts.index" plus_icon="">
                        {{ __('Contracts') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.plan')" route="candidate.plan" icon="ph-crown" path="candidate.plan" plus_icon="">
                        {{ __('plans') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.document')" route="candidate.document" icon="ph-folder-open" path="candidate.document" plus_icon="">
                        {{ __('Document') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.visa-processing.*')" route="candidate.visa-processing.index" icon="ph-passport" path="candidate.visa-processing.index" plus_icon="">
                        Visa Processing
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.profile.view')" route="candidate.profile.view" icon="ph-user-circle" path="candidate.profile.view" plus_icon="">
                        {{ __('View Profile') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('candidate.setting')" route="candidate.setting" icon="ph-gear" path="candidate.setting" plus_icon="">
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
