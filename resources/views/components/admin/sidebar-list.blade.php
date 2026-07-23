@props(['linkActive', 'route', 'icon', 'path', 'plus_icon' => '', 'routeParams' => []])

<li class="nav-item{{ filled($plus_icon) ? ' hover-icon' : '' }}">
    <a href="{{ route($route, $routeParams) }}" class="nav-link {{ $linkActive ? 'active' : '' }}">
        <i class="nav-icon {{ $icon }}"></i>
        <p class="nav-link-label mb-0">{!! $slot !!}</p>
    </a>
    @if (filled($plus_icon))
        <a href="{{ route($path, $routeParams) }}">
            <i class="{{ $plus_icon }} right ico" aria-hidden="true"></i>
        </a>
    @endif
</li>
