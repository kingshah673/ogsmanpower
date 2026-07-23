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

    @yield('ld-data')

    {{-- Style --}}
    @include('frontend.partials.styles')
    <link rel="stylesheet" href="{{ asset('css/candidate-seeker-common.css') }}?v={{ @filemtime(public_path('css/candidate-seeker-common.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/candidate-settings-classic.css') }}?v={{ @filemtime(public_path('css/candidate-settings-classic.css')) ?: '1' }}">
    @yield('css')

    {{-- Custome css and js  --}}
    {!! $setting->header_css !!}
    {!! $setting->header_script !!}
    @include('backend.layouts.partials.styles')
    <script src="{{ asset('backend/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('backend/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('backend/js/livewire.js') }}"></script>
    <script src="{{ asset('backend/js/adminlte.min.js') }}"></script>
    @unless (request()->routeIs('candidate.*', 'company.*', 'agency.*', 'agent.*'))
    <script src='https://www.google.com/recaptcha/api.js'></script>
    @endunless
    <script src="{{ asset('frontend') }}/assets/js/axios.min.js"></script>
    <script src="{{ asset('backend/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('backend') }}/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
    <script src="{{ asset('backend') }}/plugins/dropify/js/dropify.min.js"></script>
</head>

<style>
    .main-header.navbar {
        display: flex;
        align-items: center;
        min-height: 56px;
        padding: 0 0.75rem 0 0.5rem;
        background: #0A65CC !important;
        border-bottom: none;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.08);
    }
    .main-header .navbar-nav {
        flex-direction: row;
        align-items: center;
        margin: 0;
    }
    .main-header .cw-portal-title {
        flex: 1;
        text-align: center;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.02em;
        margin: 0 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
    }
    .main-header .cw-nav-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .main-header .cw-nav-actions > li {
        display: flex;
        align-items: center;
        margin: 0;
    }
    .main-header .cw-nav-actions .nav-link {
        color: #fff !important;
        padding: 0.4rem 0.55rem;
        line-height: 1;
        border-radius: 8px;
    }
    .main-header .cw-nav-actions .nav-link:hover {
        background: rgba(255, 255, 255, 0.12);
    }
    .main-header .candidate-profile img {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.35);
    }
    @media (max-width: 767px) {
        .main-header .cw-portal-title {
            font-size: 0.85rem;
            margin: 0 0.35rem;
        }
    }
    @media (max-width: 480px) {
        .main-header .cw-portal-title {
            display: none;
        }
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
            class="main-header navbar navbar-expand navbar-dark">

            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a id="nav_collapse" class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <span class="cw-portal-title">OGS MANPOWER Seeker Portal</span>
            <ul class="navbar-nav ml-auto">
                @auth('user')
                    <ul class="cw-nav-actions">
                        <li>
                            <a title="{{ __('browse_website') }}" target="_blank" class="nav-link"
                                href="{{ url('/') }}">
                                <i class="fas fa-globe"></i>
                            </a>
                        </li>

                        @if (auth()->user()->role == 'candidate')
                            <x-website.candidate.notifications-component />
                        @endif

                        <li class="dropdown dropstart">
                            <a href="javascript:void(0)" class="candidate-profile position-relative"
                                id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                @company
                                    <img src="{{ auth()->user()->company->logo_url }}" alt="logo">
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
                                @endcompany
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
                                    <a class="dropdown-item {{ request()->routeIs('candidate.profile.view') ? 'active' : '' }}"
                                        href="{{ route('candidate.profile.view') }}">
                                        <i class="ph-user-circle"></i>
                                        {{ __('View Profile') }}
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

                                @if (auth()->user()->role == 'company' || auth()->user()->role == 'candidate')
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.dashboard') ? 'active' : '' }}"
                                            href="{{ route('company.dashboard') }}">
                                            <i class="ph-stack"></i>
                                            {{ __('dashboard') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.myjob') ? 'active' : '' }}"
                                            href="{{ route('company.myjob') }}">
                                            <i class="ph-suitcase-simple"></i>
                                            {{ __('my_jobs') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.plan') ? 'active' : '' }}"
                                            href="{{ route('company.plan') }}">
                                            <i class="ph-notebook"></i>
                                            {{ __('plans_billing') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.setting') ? 'active' : '' }}"
                                            href="{{ route('company.setting') }}">
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
                        </li>
                        @if (!request()->is('email/verify'))
                            @company
                                <li class="tw-hidden sm:tw-block">

                                    <a href="{{ route('company.job.create') }}">
                                        <button class="btn btn-light">
                                            {{ __('post_job') }}
                                        </button>
                                    </a>
                                </li>
                            @endcompany
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
                    @include('frontend.partials.scripts', ['dashboard_layout' => true])

                    <!-- Custom js -->
                    {!! $setting->body_script !!}
                    <x-frontend.cookies-allowance :cookies="$cookies" />

                    <script>
                        var el = document.querySelector(".preloader");
                        el && window.addEventListener("load", function () { el.style.display = "none"; });
                    </script>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- /.content -->
        </div>

    </div>

    @include('components.website.candidate.sidebar-content')
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
            var preloader = document.querySelector('.preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
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

    @yield('script')

</body>

</html>
