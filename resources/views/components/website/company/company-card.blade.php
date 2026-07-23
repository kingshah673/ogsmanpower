@props(['company', 'variant' => 'grid'])

@php
    $profileUrl = route('website.employe.details', $company->user->username);
    $industryName = $company?->industry?->name ?? '';
@endphp

@if ($variant === 'list')
    <article class="cw-portal-list-card">
        <a href="{{ $profileUrl }}" class="cw-portal-list-card__thumb">
            <img src="{{ $company->logo_url }}" alt="{{ $company->user->name }}">
        </a>
        <div class="cw-portal-list-card__body">
            <h3 class="cw-portal-list-card__title">
                <a href="{{ $profileUrl }}">{{ $company->user->name }}</a>
            </h3>
            <div class="cw-portal-list-card__meta">
                @isset($company->country)
                    <span><i class="ph ph-map-pin"></i> {{ $company->country }}</span>
                @endisset
                @if ($industryName)
                    <span><i class="ph ph-buildings"></i> {{ $industryName }}</span>
                @endif
                <span>
                    <i class="ph ph-briefcase"></i>
                    {{ $company->activejobs }} {{ __('open_job') }}
                </span>
            </div>
        </div>
        <div class="cw-portal-list-card__actions">
            @if ($company->activejobs !== 0)
                <a href="{{ route('website.job', 'company=' . $company->user->username) }}" class="cw-portal-action-btn">
                    {{ __('open_position') }}
                </a>
            @else
                <span class="cw-portal-list-card__muted">{{ __('no_open_position') }}</span>
            @endif
        </div>
    </article>
@else
    <a href="{{ $profileUrl }}" class="cw-company-card">
        <div class="cw-company-card__top">
            <div class="cw-company-card__logo">
                <img src="{{ $company->logo_url }}" alt="{{ $company->user->name }}">
            </div>
            <div class="cw-company-card__info">
                <h3 class="cw-company-card__name">{{ $company->user->name }}</h3>
                @isset($company->country)
                    <p class="cw-company-card__loc">
                        <i class="ph ph-map-pin"></i> {{ $company->country }}
                    </p>
                @endisset
            </div>
        </div>
        <div class="cw-company-card__badges">
            @if ($industryName)
                <span class="cw-job-card__badge cw-job-card__badge--type">{{ $industryName }}</span>
            @endif
            @if ($company->activejobs !== 0)
                <span class="cw-job-card__badge cw-job-card__badge--featured">
                    {{ $company->activejobs }} {{ __('open_job') }}
                </span>
            @endif
        </div>
        <div class="cw-company-card__footer">
            <span>{{ __('view_profile') }}</span>
            <i class="ph-bold ph-arrow-right"></i>
        </div>
    </a>
@endif
