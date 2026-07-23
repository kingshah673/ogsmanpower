@props(['candidates'])

<div class="tab-pane" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
    <div class="cw-portal-list">
        @if ($candidates->count() > 0)
            @foreach ($candidates as $candidate)
                @if (optional($candidate->user)->username != '')
                    <x-website.candidate.candidate-card :candidate="$candidate" variant="list" />
                @endif
            @endforeach
        @else
            <div class="card text-center">
                <x-not-found message="{{ __('no_data_found') }}" />
            </div>
        @endif
    </div>
</div>
