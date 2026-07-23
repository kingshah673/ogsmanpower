<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    {{-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> @yield('title') - {{ config('app.name') }} </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description')">
    <meta property="og:image" content="@yield('og:image')">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    {{-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" /> --}}
    <script src="{{ asset('backend/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('backend/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('backend/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('backend/js/ckeditor.js') }}"></script>
    <script src="{{ asset('backend/js/livewire.js') }}"></script>
    <script src="{{ asset('backend/js/adminlte.min.js') }}"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <script src="{{ asset('frontend') }}/assets/js/axios.min.js"></script>
    <script src="{{ asset('backend/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('backend') }}/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
    <script src="{{ asset('backend') }}/plugins/dropify/js/dropify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <title>@yield('title') - {{ config('app.name') }}</title>

    @yield('ld-data')

    {{-- Style --}}
    @include('frontend.partials.styles')
    <link rel="stylesheet" href="{{ asset('css/candidate-seeker-common.css') }}?v={{ @filemtime(public_path('css/candidate-seeker-common.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/candidate-settings-classic.css') }}?v={{ @filemtime(public_path('css/candidate-settings-classic.css')) ?: '1' }}">
    {{-- @include('frontend.partials.preloader') --}}
    @yield('css')

    {{-- Custome css and js  --}}
    {!! $setting->header_css !!}
    {!! $setting->header_script !!}
    @include('backend.layouts.partials.styles')
</head>

<style>
   /* =========================
   SAAS TOPBAR (LEFT TITLE)
   ========================= */

/* NAVBAR BASE */
.main-header.navbar {
    background: linear-gradient(180deg, #1E2A5A 0%, #162454 100%) !important;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding: 10px 16px;
}

/* TITLE (LEFT SIDE) */
.title {
    position: static; /* remove absolute */
    transform: none;
    margin-left: 10px;

    color: #fff;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.4px;
    opacity: 0.9;

    display: flex;
    align-items: center;
}

/* SMALL SUBTEXT STYLE */
.title span {
    margin-left: 6px;
    font-size: 11px;
    color: rgba(255,255,255,0.5);
}

/* MOBILE */
@media (max-width: 768px) {
    .title {
        display: none;
    }
}
.upload-box {
    cursor: pointer;
    transition: 0.3s;
    background: #fafafa;
    min-height: 140px;   /* âœ… IMPORTANT */
    display: flex;       /* âœ… IMPORTANT */
    align-items: center; /* âœ… center content */
    justify-content: center;
    flex-direction: column;
}

.upload-box:hover {
    background: #f1f7ff;
    border-color: #0d6efd;
}
.file-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    top: 0;
    left: 0;
    cursor: pointer;
    z-index: 2; /* âœ… IMPORTANT */
}
.upload-box {
    border: 2px dashed #dcdcdc;
}
</style>

<body class="hold-transition sidebar-mini layout-fixed {{ $setting->dark_mode ? 'dark-mode' : '' }}"
    dir="{{ langDirection() }}">
    <input type="hidden" value="{{ current_country_code() }}" id="current_country_code">
    <input type="hidden" id="auth_user" value="{{ auth()->check() ? 1 : 0 }}">
    <input type="hidden" id="auth_user_id" value="{{ auth()->check() ? auth()->id() : 0 }}">
    @php
        $user = auth()->user();
    @endphp
    <div class="wrapper">
        <!-- Navbar -->
        <nav id="nav"
            class="main-header navbar navbar-expand {{ $setting->dark_mode ? 'navbar-dark navbar-dark' : 'navbar-white navbar-light' }}">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item ">
                    <a id="nav_collapse" class="nav-link" data-widget="pushmenu" href="#" role="button"
                        style="color: white"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Centered title -->
            <span class="title "> {{ auth()->user()->name }} AGENCY PORTAL</span>
            <ul class="navbar-nav ml-auto">
                @auth('user')
                    <ul class="list-unstyled tw-gap-6 d-flex ">
                        <li style="margin-top:5px;">
                            <a title="{{ __('browse_website') }}" target="_blank" class="nav-link" style="color: white;"
                                href="{{ url('/') }}">
                                <i class="fas fa-globe fa-2"></i>
                            </a>
                        </li>

                        @if (auth()->user()->role == 'candidate')
                            <x-website.candidate.notifications-component />
                        @endif

                        <div class="dropdown dropstart">
                            <a href="javascript:void(0)" class="candidate-profile position-relative"
                                id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                @agency
                                    <img src="{{ auth()->user()->agency->logo_url }}" alt="logo">
                                @else
                                    <img src="{{ auth()->user()->candidate->photo }}" alt="photo">
                                    @if (auth()->user()->candidate->status == 'available')
                                        <span class="available-alert-header">
                                            <svg class="circle" width="14" height="14" viewBox="0 0 14 14" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="7" cy="7" r="6" fill="#2ecc71" stroke="white"
                                                    stroke-width="2">
                                                </circle>
                                            </svg>
                                        </span>
                                    @endif
                                @endagency
                            </a>
                            @candidate
                            <ul class="custom-border dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('candidate.dashboard') ? 'active' : '' }}"
                                        href="{{ route('candidate.dashboard') }}">
                                        <i class="ph-stack"></i>
                                        {{ __('dashboard') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('candidate.setting') ? 'active' : '' }}"
                                        href="{{ route('candidate.setting') }}">
                                        <i class="ph-gear"></i>
                                        {{ __('settings') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="ph-sign-out"></i>
                                        {{ __('log_out') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        @else
                            <ul class="dropdown-menu custom-border" aria-labelledby="dropdownMenuButton1">

                                @if (auth()->user()->role == 'agency' || auth()->user()->role == 'candidate')
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('agency.dashboard') ? 'active' : '' }}"
                                            href="{{ route('agency.dashboard') }}">
                                            <i class="ph-stack"></i>
                                            {{ __('dashboard') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('agency.myjob') ? 'active' : '' }}"
                                            href="{{ route('agency.myjob') }}">
                                            <i class="ph-suitcase-simple"></i>
                                            {{ __('my_jobs') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('agency.plan') ? 'active' : '' }}"
                                            href="{{ route('agency.plan') }}">
                                            <i class="ph-notebook"></i>
                                            {{ __('plans_billing') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('agency.setting') ? 'active' : '' }}"
                                            href="{{ route('agency.setting') }}">
                                            <i class="ph-gear"></i>
                                            {{ __('settings') }}
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="ph-sign-out"></i>
                                        {{ __('log_out') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                            @endcandidate
                        </div>
                        @if (!request()->is('email/verify'))
                            @agency
                                <li class="tw-hidden sm:tw-block">

                                    <a href="{{ route('agency.job.create') }}">
                                        <button class="btn btn-light">
                                            {{ __('post_job') }}
                                        </button>
                                    </a>
                                </li>
                            @endagency
                        @endif
                        @if (request()->is('email/verify'))
                            <li>
                                <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <button class="btn btn-primary">
                                        {{ __('log_out') }}
                                    </button>
                                </a>
                            </li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        @endif
                    </ul>
                @endauth
            </ul>
        </nav>

        <x-frontend.cookies-allowance :cookies="$cookies" />
        <!-- Support Menu -->
        @if (!config('app.hide_helper'))
            <x-help-widget></x-help-widget>
        @endif
        <!-- Main Sidebar Container -->
        <div class="content-wrapper">

            <div class="content">
                <div class="container-fluid">
                    @yield('main')
                    <!-- /.row -->
                    @include('frontend.partials.scripts')

                    <!-- Custom js -->
                    {!! $setting->body_script !!}
                    <x-frontend.cookies-allowance :cookies="$cookies" />

                    <script>
                        // Hide the preloader when loaded
                        var el = document.querySelector(".preloader");
                        el && window.addEventListener("load", () => el.style.display = "none");
                    </script>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- /.content -->
        </div>

    </div>

    @include('components.website.agency.sidebar-content')
    @include('backend.layouts.partials.footer')
    {{-- @include('backend.layouts.partials.scripts') --}}

    <script>
        // Navbar Collapse Toggle
        var isNavCollapse = JSON.parse(localStorage.getItem("sidebar_collapse"))
        isNavCollapse ? $('body').addClass('sidebar-collapse') : null;

        $('#nav_collapse').on('click', function() {
            localStorage.setItem("sidebar_collapse", isNavCollapse == true ? false : true);
        });
    </script>
    <script>
        window.onload = function() {
            document.querySelector('.preloader').style.display = 'none';
        };
    </script>
    @if ($setting->pwa_enable)
        <button class="pwa-install-btn bg-white position-fixed d-none" id="installApp">
            <img src="{{ asset('pwa-btn.png') }}" alt="Install App" loading="lazy">
        </button>
        <script src="{{ asset('/sw.js') }}"></script>
        <script>
            if (!navigator.serviceWorker) {
                navigator.serviceWorker.register("/sw.js").then(function(reg) {
                    console.log("Service worker has been registered for scope: " + reg);
                });
            }

            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                $('#installApp').removeClass('d-none');
                deferredPrompt = e;
            });

            const installApp = document.getElementById('installApp');
            installApp.addEventListener('click', async () => {
                if (deferredPrompt !== null) {
                    deferredPrompt.prompt();
                    const {
                        outcome
                    } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        deferredPrompt = null;
                    }
                }
            });
        </script>
    @endif

    @include('frontend.partials.sophia-widget')

</body>

</html>
