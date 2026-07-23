<style>
   /* ======================================
   OGS PREMIUM SIDEBAR — FINAL VERSION
   ====================================== */

/* ===== BASE SIDEBAR ===== */
.main-sidebar {
    width: 220px;
    background: linear-gradient(180deg, #1E2A5A 0%, #0F1B3D 100%) !important;
    border-right: none !important;
    transition: all 0.25s ease;
}

/* ===== MINI MODE ===== */
body.sidebar-mini-collapse .main-sidebar {
    width: 75px;
}

/* HIDE TEXT IN MINI */
body.sidebar-mini-collapse .nav-link p,
body.sidebar-mini-collapse .brand-text,
body.sidebar-mini-collapse .nav-header,
body.sidebar-mini-collapse .profile_section h6,
body.sidebar-mini-collapse .profile_section p {
    display: none !important;
}

/* CENTER ICONS */
body.sidebar-mini-collapse .nav-link {
    justify-content: center;
}

/* ===== LOGO ===== */
.brand-link {
    padding: 18px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    color: #fff !important;
    font-size: 14px;
    font-weight: 700;
}

/* ===== PROFILE CARD ===== */
.profile_section {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    margin: 10px;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-image {
    width: 36px !important;
    height: 36px !important;
    border-radius: 10px !important;
}

/* ===== NAV HEADER ===== */
.nav-header {
    font-size: 9px !important;
    color: rgba(255,255,255,0.35) !important;
    margin: 14px 10px 6px;
    letter-spacing: 1px;
}

/* ===== NAV ITEM ===== */
.nav-sidebar .nav-link {
    border-radius: 10px;
    margin: 3px 8px;
    padding: 9px 10px;
    font-size: 12.5px;
    color: rgba(255,255,255,0.65);
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background 0.15s ease, color 0.15s ease;
}

/* ICON */
.nav-icon {
    font-size: 14px !important;
    width: 18px;
    text-align: center;
    opacity: 0.9;
}

/* ===== HOVER (SOFT LIKE YOUR SCREENSHOT) ===== */
.nav-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.05);
    color: #ffffff;
}

/* REMOVE ANY MOVEMENT */
.nav-sidebar .nav-link:hover {
    transform: none !important;
}

/* ===== ACTIVE (ONLY ACTIVE HAS GRADIENT) ===== */
.nav-sidebar .nav-link.active {
    background: linear-gradient(135deg, #2F6BFF, #7C3AED);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(47,107,255,0.25);
    position: relative;
}

/* ACTIVE LEFT INDICATOR */
.nav-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 20%;
    height: 60%;
    width: 3px;
    background: #2F6BFF;
    border-radius: 0 3px 3px 0;
}

/* ===== DROPDOWN ===== */
.nav-treeview {
    padding-left: 15px;
}

/* ===== BADGES ===== */
.nav-badge {
    margin-left: auto;
    font-size: 8.5px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 8px;
}

/* BADGE COLORS */
.nb-blue {
    background: rgba(59,130,246,0.2);
    color: #93C5FD;
}

.nb-green {
    background: rgba(16,185,129,0.2);
    color: #6EE7B7;
}

.nb-red {
    background: rgba(239,68,68,0.2);
    color: #FCA5A5;
}

.nb-gold {
    background: rgba(245,158,11,0.2);
    color: #FCD34D;
}

/* ===== FOOTER ===== */
.nav-footer .nav-link {
    border-radius: 10px;
    margin: 4px 8px;
}

/* ===== SCROLLBAR ===== */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
}

/* ===== SMOOTH ===== */
.main-sidebar,
.nav-link,
.profile_section {
    transition: all 0.25s ease;
}
</style>
<aside id="sidebar" class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('agency.dashboard') }}" class="brand-link">
        <!--<img src="{{ $setting->favicon_image_url }}" alt="{{ __('logo') }}" class="elevation-3">-->
        <span class="brand-text font-weight-dark" style="font-weight: bolder; margin-left: 20px" >AGENCY PORTAL</span>
    </a>
    


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-nav-wrapper">


            <!-- Sidebar Menu -->
            <nav class="sidebar-main-nav mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <x-admin.sidebar-list :linkActive="Route::is('agency.dashboard') ? true : false" route="agency.dashboard" parameter=""
                        path="agency.dashboard" plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('dashboard') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="Route::is('agency.myjob') ? true : false" route="agency.myjob" parameter="" path="agency.myjob"
                        plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('my_jobs') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="Route::is('agency.available.jobs') ? true : false" route="agency.available.jobs" parameter="" path="agency.available.jobs"
                        plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('assigned_jobs') }}
                    </x-admin.sidebar-list>
                    
                    <x-admin.sidebar-list :linkActive="Route::is('agency.pipeline') ? true : false" route="agency.pipeline" parameter="" path="agency.pipeline"
                        plus_icon="" icon="fas fa-users">
                        {{ __('candidate_pipeline') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.interviews') ? true : false" route="agency.interviews" parameter="" path="agency.interviews"
                        plus_icon="" icon="ph-video-camera">
                        {{ __('interviews') }}
                    </x-admin.sidebar-list>


                    @if (!$setting->edited_job_auto_approved)
                        <x-admin.sidebar-list :linkActive="request()->routeIs('agency.pending.edited.jobs') ? true : false" route="agency.pending.edited.jobs" icon="ph-person"
                            path="agency.pending.edited.jobs" plus_icon="">
                            {{ __('pending_edited_jobs') }}
                        </x-admin.sidebar-list>
                    @endif

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.job.create') ? true : false" route="agency.job.create" icon="ph-suitcase-simple"
                        path="agency.job.create" plus_icon="">
                        {{ __('post_a_job') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('contracts.*')" route="contracts.index" icon="ph-file-text" path="contracts.index" plus_icon="">
                    {{ __('contracts') }}
                    </x-admin.sidebar-list>
                    
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.candidates.*') ? true : false" route="agency.candidates.index" icon="fas fa-user" 
                    path="agency.candidates.index" plus_icon="">
                    {{ __('candidate') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.bookmark') ? true : false" route="agency.bookmark" icon="ph-bookmark-simple"
                        path="agency.bookmark" plus_icon="">
                        {{ __('saved_candidate') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.questions.manage') ? true : false" route="agency.questions.manage" icon="ph-bell-ringing"
                        path="agency.questions.manage" plus_icon="">
                        {{ __('custom_questions') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.my.agents') ? true : false" route="agency.my.agents" icon="ph-users-three"
                        path="agency.my.agents" plus_icon="">
                        {{ __('my_agents') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.invitations') ? true : false" route="agency.invitations" icon="ph-envelope-simple-open"
    path="agency.invitations" plus_icon="">

{{ __('invitations') }}
</x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.plan') ? true : false" route="agency.plan" icon="ph-bell-ringing"
                        path="agency.plan" plus_icon="">
                        {{ __('plans_billing') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.verify.documents.index') ? true : false" route="agency.verify.documents.index" icon="ph-gear"
                        path="agency.verify.documents.index" plus_icon="">
                        {{ __('verify_account') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.candidate-status') ? true : false" route="agency.candidate-status" icon="fas fa-user"
                        path="agency.candidate-status" plus_icon="">
                        {{ __('Status of Workers') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.visa-processing.*')" route="agency.visa-processing.index" icon="ph-passport" path="agency.visa-processing.index" plus_icon="">
                        {{ __('visa_processing') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.protector.*')" route="agency.protector.index" icon="ph-shield-check" path="agency.protector.index" plus_icon="">
                        {{ __('protector_clearance') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.nominated-workers.*')" route="agency.nominated-workers.index" icon="ph-users-four" path="agency.nominated-workers.index" plus_icon="">
                        {{ __('nominated_workers') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.commissions.*')" route="agency.commissions.index" icon="ph-currency-circle-dollar" path="agency.commissions.index" plus_icon="">
                        {{ __('commissions') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.reports.*')" route="agency.reports.index" icon="ph-chart-bar" path="agency.reports.index" plus_icon="">
                        {{ __('reports') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.ai.*')" route="agency.ai.summary" icon="ph-sparkle" path="agency.ai.summary" plus_icon="">
                        {{ __('ai_insights') }}
                    </x-admin.sidebar-list>
                    <x-admin.sidebar-list :linkActive="request()->routeIs('agency.setting') ? true : false" route="agency.setting" icon="ph-gear"
                        path="agency.setting" plus_icon="">
                        {{ __('settings') }}
                    </x-admin.sidebar-list>

                    <x-admin.sidebar-list :linkActive="request()->routeIs('website.home')" route="website.home" icon="ph-globe" path="website.home" plus_icon="">
                        {{ __('View Website') }}
                    </x-admin.sidebar-list>

                    {{-- <x-admin.sidebar-list :linkActive=" request()->routeIs('logout') ? 'active' : '' " route="logout" icon="ph-sign-out" path="logout"
                        plus_icon=""
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('log_out') }}

                        <!-- Hidden Logout Form -->
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </x-admin.sidebar-list> --}}




                </ul>


                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('website.employe.details', auth()->user()->username) }}"
                            class="{{ linkActive('agency.verify.documents.index') }}">
                            <span class="button-content-wrapper tw-items-center">
                                <span class="button-icon align-icon-left tw-flex tw-items-center">
                                    <i class="ph-user-circle"></i>
                                </span>
                                <span class="button-text">
                                    {{ __('my_profile') }}
                                </span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="{{ request()->routeIs('logout') ? 'active' : '' }}" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="button-content-wrapper ">
                                <span class="button-icon align-icon-left tw-flex tw-items-center">
                                    <i class="ph-sign-out"></i>
                                </span>
                                <span class="button-text">
                                    {{ __('log_out') }}
                                </span>
                            </span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>

                </ul>
            </nav>
            <!-- Sidebar Menu -->

        </div>
    </div>
    <!-- /.sidebar -->
</aside>
