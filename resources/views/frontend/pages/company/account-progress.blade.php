@extends('components.website.company.layout.app')

@section('title')
    {{ __('setup_progress') }}
@endsection

@section('main')
@php
    $company = auth()->user()->company;
    $progress = 0;
    if ($company->logo && $company->bio) {
        $progress = 25;
    }
    if ($company->organization_type_id && $company->industry_type_id && $company->vision) {
        $progress = 50;
    }
    if ($company->user?->socialInfo?->isNotEmpty()) {
        $progress = max($progress, 75);
    }
    if ($company->profile_completion) {
        $progress = 100;
    }
@endphp

<div class="dashboard-wrapper seeker-settings-page account-progress-page">
    <div class="container">
        <div class="dashboard-right">

            <div class="cw-settings-header">
                <div>
                    <h1>{{ __('Complete Your Company Profile') }}</h1>
                    <p>{{ __('Fill in all sections below to unlock your employer dashboard and start posting jobs.') }}</p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>{{ __('please_fix_the_following_errors') }}:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="glass-card account-progress-status">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="text-muted small mb-1">{{ __('setup_progress') }}</p>
                        <h5 class="mb-0 fw-semibold">{{ $progress }}% {{ __('completed') }}</h5>
                    </div>
                    <div class="flex-grow-1 account-progress-status__bar">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cw-settings-layout">
                <nav class="cw-chapter-nav" aria-label="{{ __('setup_progress') }}">
                    <p class="cw-chapter-nav__label">{{ __('Profile Sections') }}</p>
                    <a href="#section-branding" class="cw-chapter-link is-active">
                        <i class="far fa-image"></i> {{ __('logo_banner_image') }}
                    </a>
                    <a href="#section-profile" class="cw-chapter-link">
                        <i class="far fa-building"></i> {{ __('profile') }}
                    </a>
                    <a href="#section-social" class="cw-chapter-link">
                        <i class="fas fa-share-alt"></i> {{ __('social_media') }}
                    </a>
                    <a href="#section-contact" class="cw-chapter-link">
                        <i class="far fa-envelope"></i> {{ __('contact') }}
                    </a>
                </nav>

                <div class="cw-settings-content account-progress-long-form">
                    <div id="msform" class="account-progress-wrap company ll-progress">
                        <div id="section-branding" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('logo_banner_image') }}
                                    </h3>
                                </div>
                                <x-website.company.account-progress.personal :user="$user" long-form />
                            </div>
                        </div>

                        <div id="section-profile" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('profile') }}
                                    </h3>
                                </div>
                                <x-website.company.account-progress.profile
                                    :user="$user"
                                    :countries="$countries"
                                    :organization-types="$organization_types"
                                    :industry-types="$industry_types"
                                    :team-sizes="$team_sizes"
                                    long-form
                                />
                            </div>
                        </div>

                        <div id="section-social" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('social_media') }}
                                    </h3>
                                </div>
                                <x-website.company.account-progress.social :socials="$socials" long-form />
                            </div>
                        </div>

                        <div id="section-contact" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('contact') }}
                                    </h3>
                                </div>
                                <x-website.company.account-progress.contact :user="$user" long-form />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('css')
    @if (config('templatecookie.map_show'))
        <x-map.leaflet.map_links />
        <x-map.leaflet.autocomplete_links />
    @endif
    <style>
        .account-progress-page .account-progress-status {
            margin-bottom: 1.5rem;
        }
        .account-progress-page .account-progress-status__bar {
            max-width: 420px;
        }
        .account-progress-long-form #msform fieldset {
            display: block !important;
            border: 0;
            margin: 0;
            padding: 0;
            min-width: 0;
        }
        .account-progress-long-form .form-card {
            padding: 0;
            box-shadow: none;
            border: none;
            background: transparent;
        }
        .account-progress-long-form .dashboard-account-setting-item {
            padding-bottom: 0;
        }
        .account-progress-long-form .dashboard-account-setting-item + .dashboard-account-setting-item {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-slate-200, #e2e8f0);
        }
        .account-progress-long-form .mborder {
            border: 1px solid var(--color-slate-200, #e2e8f0);
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
        }
        .account-progress-long-form form .btn-primary {
            margin-top: 1.25rem;
        }
        .account-progress-page .mymap,
        .account-progress-page #leaflet-map {
            border-radius: 12px;
        }
        .account-progress-page #leaflet-map {
            height: 300px;
        }
        .account-progress-page .country-state-city-root .select-wrapper {
            gap: 12px;
        }

        /* Logo / banner change — always visible (not hover-only) */
        .logo-current-wrap {
            width: 140px;
            height: 140px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            background: #f8fafc;
        }
        .logo-current-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        .logo-change-overlay,
        .banner-change-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 1;
            font-size: 12px;
            font-weight: 600;
            gap: 4px;
            pointer-events: none;
        }
        .banner-current-wrap {
            width: 100%;
            min-height: 120px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        .banner-current-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .logo-change-btn,
        .banner-change-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .logo-change-btn:hover,
        .banner-change-btn:hover {
            border-color: #0a65cc;
            color: #0a65cc;
            background: #eff6ff;
        }
    </style>
@endsection

@section('script')
    <x-website.account-progress.scripts :long-form="true" />
    <script>
    (function () {
        var sections = document.querySelectorAll('.account-progress-page .form-section-anchor');
        var links = document.querySelectorAll('.account-progress-page .cw-chapter-link');
        if (!sections.length || !links.length) {
            return;
        }

        function setActiveChapter() {
            var current = sections[0].id;
            var offset = 120;
            sections.forEach(function (section) {
                if (window.scrollY >= section.offsetTop - offset) {
                    current = section.id;
                }
            });
            links.forEach(function (link) {
                link.classList.toggle('is-active', link.getAttribute('href') === '#' + current);
            });
        }

        window.addEventListener('scroll', setActiveChapter, { passive: true });
        setActiveChapter();
    })();
    </script>
@endsection
