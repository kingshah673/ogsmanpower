@props(['title', 'subtitle' => null])

<div class="cw-settings-header">
    <div>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
