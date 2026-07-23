@props(['compact' => false])

@if(!empty($profileCompletionMissing) && ($completionPercentage ?? 100) < 100)
    @php
        $settingsUrl = route('candidate.setting');
    @endphp
    <div class="cw-profile-completion-hints {{ $compact ? 'cw-profile-completion-hints--compact' : '' }}">
        <p class="cw-profile-completion-hints__title">
            {{ __('Complete your profile to reach 100%') }}
            <span class="cw-profile-completion-hints__pct">({{ number_format($completionPercentage ?? 0, 0) }}%)</span>
        </p>
        <ul class="cw-profile-completion-hints__list">
            @foreach($profileCompletionMissing as $item)
                <li>
                    <a href="{{ $settingsUrl }}#{{ $item['anchor'] }}" class="cw-profile-completion-hints__link">
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $item['hint'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
