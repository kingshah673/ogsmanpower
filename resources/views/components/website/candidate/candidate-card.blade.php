@props(['candidate', 'variant' => 'grid'])

@php
    $username = $candidate->user->username ?? '';
    $displayName = auth('user')->check()
        ? ($candidate->user->name ?? '')
        : maskFullName($candidate->user->name ?? '');
    $professionName = $candidate->profession ? $candidate->profession->name : '';
@endphp

@if ($variant === 'list')
    <article class="cw-portal-list-card {{ !auth('user')->check() ? 'login_required' : '' }}">
        <div class="cw-portal-list-card__thumb cw-candidate-card__avatar-wrap">
            <img src="{{ asset($candidate->photo) }}" alt="{{ __('candidate_image') }}">
            @if ($candidate->status == 'available')
                <span class="cw-candidate-card__status"></span>
            @endif
        </div>
        <div class="cw-portal-list-card__body">
            <h3 class="cw-portal-list-card__title">
                @if (auth('user')->check())
                    <a href="javascript:void(0)" onclick="showCandidateProfileModal('{{ $username }}')">{{ $displayName }}</a>
                @else
                    <a href="javascript:void(0)" class="login_required">{{ $displayName }}</a>
                @endif
            </h3>
            <div class="cw-portal-list-card__meta">
                @if ($professionName)
                    <span><i class="ph ph-briefcase"></i> {{ $professionName }}</span>
                @endif
                @if ($candidate->country)
                    <span><i class="ph ph-map-pin"></i> {{ $candidate->country }}</span>
                @endif
                @if ($candidate->experience)
                    <span><i class="ph ph-chart-line-up"></i> {{ $candidate->experience->name }}</span>
                @endif
            </div>
        </div>
        <div class="cw-portal-list-card__actions">
            @if (auth('user')->check())
                <form action="{{ route('company.hire-request') }}" method="POST" onclick="event.stopPropagation()">
                    @csrf
                    <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                    <button type="submit" class="cw-candidate-card__book-btn">{{ __('Book Me') }}</button>
                </form>
            @else
                <span class="cw-candidate-card__book-btn login_required">{{ __('Book Me') }}</span>
            @endif
            @if ($candidate->already_view)
                <div data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('you_have_seen_the_cv_on') }} {{ $candidate->already_views && $candidate->already_views[0] ? $candidate->already_views[0]->view_date_time : '-' }}. After {{ $candidate->already_views && $candidate->already_views[0] ? $candidate->already_views[0]->expired_date : '-' }} {{ __('the_view_count_will_be_reset') }}"
                    class="cw-candidate-card__viewed" id="cv_view">
                    <x-svg.eye-icon fill="#767F8C" />
                </div>
            @else
                <div class="d-none cw-candidate-card__viewed" id="cv_view{{ $candidate->id }}">
                    <x-svg.eye-icon fill="#767F8C" />
                </div>
            @endif
        </div>
    </article>
@else
    <article class="cw-candidate-card {{ !auth('user')->check() ? 'login_required' : '' }}"
        @if (auth('user')->check()) onclick="showCandidateProfileModal('{{ $username }}')" style="cursor:pointer" @endif>
        <div class="cw-candidate-card__top">
            <button type="button" class="cw-candidate-card__share" onclick="event.stopPropagation(); copyToClipboard(event)" title="{{ __('share') }}">
                <x-svg.share-icon />
            </button>
            <div class="cw-candidate-card__avatar-wrap">
                <img src="{{ asset($candidate->photo) }}" alt="{{ __('candidate_image') }}">
                @if ($candidate->status == 'available')
                    <span class="cw-candidate-card__status"></span>
                @endif
            </div>
            <h3 class="cw-candidate-card__name">
                @if (auth('user')->check())
                    <a href="javascript:void(0)" onclick="showCandidateProfileModal('{{ $username }}')">{{ $displayName }}</a>
                @else
                    <span class="login_required">{{ $displayName }}</span>
                @endif
            </h3>
            @if ($professionName)
                <p class="cw-candidate-card__profession">{{ $professionName }}</p>
            @endif
            @if ($candidate->status == 'available')
                <p class="cw-candidate-card__available">{{ __('i_am_available') }}</p>
            @endif
        </div>
        <div class="cw-candidate-card__footer" onclick="event.stopPropagation()">
            @if (auth('user')->check())
                <form action="{{ route('company.hire-request') }}" method="POST">
                    @csrf
                    <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                    <button type="submit" class="cw-candidate-card__book-btn">{{ __('Book Me') }}</button>
                </form>
            @else
                <span class="cw-candidate-card__book-btn login_required">{{ __('Book Me') }}</span>
            @endif
            @if ($candidate->already_view)
                <div data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('you_have_seen_the_cv_on') }} {{ $candidate->already_views && $candidate->already_views[0] ? $candidate->already_views[0]->view_date_time : '-' }}. After {{ $candidate->already_views && $candidate->already_views[0] ? $candidate->already_views[0]->expired_date : '-' }} {{ __('the_view_count_will_be_reset') }}"
                    class="cw-candidate-card__viewed" id="cv_view">
                    <x-svg.eye-icon fill="#767F8C" />
                </div>
            @else
                <div class="d-none cw-candidate-card__viewed" id="cv_view{{ $candidate->id }}">
                    <x-svg.eye-icon fill="#767F8C" />
                </div>
            @endif
        </div>
    </article>
@endif
