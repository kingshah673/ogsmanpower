@extends('components.website.agency.layout.app')

@section('title')
    {{ __('setup_progress') }}
@endsection

@section('main')
@php
    $agency = auth()->user()->agency;
    $progress = 0;
    if ($agency->logo && $agency->bio) {
        $progress = 25;
    }
    if ($agency->organization_type_id && $agency->industry_type_id && $agency->vision) {
        $progress = 50;
    }
    if ($agency->user?->socialInfo?->isNotEmpty()) {
        $progress = max($progress, 75);
    }
    if ($agency->profile_completion) {
        $progress = 100;
    }
@endphp

<div class="dashboard-wrapper seeker-settings-page account-progress-page">
    <div class="container">
        <div class="dashboard-right">

            <div class="cw-settings-header">
                <div>
                    <h1>{{ __('Complete Your Agency Profile') }}</h1>
                    <p>{{ __('Fill in all sections below to unlock your agency dashboard.') }}</p>
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
                    <div id="msform" class="account-progress-wrap agency ll-progress">
                        <div id="section-branding" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('logo_banner_image') }}
                                    </h3>
                                </div>
                                <x-website.agency.account-progress.personal :user="$user" long-form />
                            </div>
                        </div>

                        <div id="section-profile" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('profile') }}
                                    </h3>
                                </div>
                                <x-website.agency.account-progress.profile
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
                                <x-website.agency.account-progress.social :socials="$socials" long-form />
                            </div>
                        </div>

                        <div id="section-contact" class="glass-card form-section-anchor">
                            <div class="glass-card-body">
                                <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                        {{ __('contact') }}
                                    </h3>
                                </div>
                                <x-website.agency.account-progress.contact :user="$user" long-form />
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
