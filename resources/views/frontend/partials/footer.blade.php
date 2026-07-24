{{-- OGS FOOTER START --}}
@php
    $footerBg = $cms_setting->footer_bg_color ?? '#2b2f3a';
    $footerText = $cms_setting->footer_text_color ?? '#cbd5e1';
    $footerAccent = $cms_setting->footer_accent_color ?? '#38bdf8';
    $footerCopyright = $cms_setting->footer_copyright ?? 'Copyright © 2012 OGSmanpower.com. All rights reserved.';
    $footerPowered = $cms_setting->footer_powered_by ?? 'OGSmanpower';
    $badgeEnabled = (bool) ($cms_setting->footer_badge_enabled ?? true);
    $badgePos = $cms_setting->footer_badge_position ?? 'right';
    $badgeSrc = !empty($cms_setting->footer_badge_image)
        ? asset('storage/' . $cms_setting->footer_badge_image)
        : asset('icons/15yearslogo.png');

    $panels = $footerPanels ?? collect();
    $topLinksPanel = $panels->first(function ($p) {
        return strtolower(trim((string) $p->title)) === 'top links';
    });
    $columnPanels = $panels->filter(function ($p) {
        return strtolower(trim((string) $p->title)) !== 'top links';
    })->values();
@endphp

<footer class="ogs-footer" style="--ogs-footer-bg: {{ $footerBg }}; --ogs-footer-text: {{ $footerText }}; --ogs-footer-accent: {{ $footerAccent }};">
    <style>
    .ogs-footer {
        background: var(--ogs-footer-bg, #2b2f3a);
        color: var(--ogs-footer-text, #cbd5e1);
        padding: 40px 0 20px;
        font-size: 14px;
        position: relative;
    }
    .ogs-footer a { color: var(--ogs-footer-text, #cbd5e1); text-decoration: none; }
    .ogs-footer a:hover { color: #fff; }
    .footer-top { text-align: center; margin-bottom: 20px; }
    .footer-top a { margin: 0 10px; }
    .footer-top span { color: #64748b; }
    .ogs-footer h5 { color: #fff; font-weight: 600; margin-bottom: 15px; }
    .ogs-footer ul { list-style: none; padding: 0; margin: 0; }
    .ogs-footer ul li { margin-bottom: 8px; padding-left: 12px; position: relative; }
    .ogs-footer ul li::before {
        content: "▪";
        color: var(--ogs-footer-accent, #38bdf8);
        position: absolute;
        left: 0;
    }
    .ogs-footer hr { border-color: #3b4252; margin: 20px 0; }
    .footer-bottom {
        border-top: 1px solid #3b4252;
        margin-top: 20px;
        padding-top: 15px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        font-size: 13px;
        gap: 10px;
    }
    .footer-bottom p { margin: 0; }
    .footer-badge-wrap {
        display: flex;
        justify-content: {{ $badgePos === 'left' ? 'flex-start' : ($badgePos === 'center' ? 'center' : 'flex-end') }};
        margin-bottom: 10px;
    }
    .footer-badge-wrap img {
        width: 100px;
        height: auto;
        border-radius: 50%;
        background: #fff;
        padding: 5px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .ogs-footer .footer-socials a { margin-right: 12px; font-size: 18px; }
    @media (max-width: 768px) {
        .footer-bottom { text-align: center; justify-content: center; }
        .footer-badge-wrap { justify-content: center; }
    }
    </style>

    <div class="container">
        @if ($badgeEnabled)
            <div class="footer-badge-wrap">
                <img src="{{ $badgeSrc }}" alt="15 Years Experience">
            </div>
        @endif

        @if ($topLinksPanel && $topLinksPanel->items->count())
            <div class="footer-top">
                @foreach ($topLinksPanel->items as $i => $item)
                    @if ($i > 0)<span>|</span>@endif
                    @if ($item->type === 'link')
                        <a href="{{ $item->url ?: '#' }}" @if($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                    @else
                        <span>{{ $item->label ?: $item->content }}</span>
                    @endif
                @endforeach
            </div>
            <hr>
        @endif

        <div class="row">
            @forelse ($columnPanels as $panel)
                <div class="col-lg-3 col-md-6 mb-4">
                    @if ($panel->title)
                        <h5>{{ $panel->title }}</h5>
                    @endif
                    <ul>
                        @foreach ($panel->items as $item)
                            <li>
                                @if ($item->type === 'link')
                                    <a href="{{ $item->url ?: '#' }}" @if($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                                @elseif ($item->type === 'heading')
                                    <strong>{{ $item->label }}</strong>
                                @elseif ($item->type === 'image' && $item->image_path)
                                    <a href="{{ $item->url ?: '#' }}">
                                        <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->label }}" style="max-width:120px;">
                                    </a>
                                @else
                                    {!! nl2br(e($item->content ?: $item->label)) !!}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="col-12 text-center text-muted">
                    <p>Footer content is managed from Admin → Footer CMS.</p>
                </div>
            @endforelse
        </div>

        @php
            $socials = array_filter([
                'facebook' => $cms_setting->footer_facebook_link ?? null,
                'instagram' => $cms_setting->footer_instagram_link ?? null,
                'twitter' => $cms_setting->footer_twitter_link ?? null,
                'youtube' => $cms_setting->footer_youtube_link ?? null,
            ]);
        @endphp
        @if (count($socials))
            <div class="footer-socials text-center mb-3">
                @foreach ($socials as $network => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $network }}">
                        <i class="fab fa-{{ $network === 'twitter' ? 'x-twitter' : $network }}"></i>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="footer-bottom">
            <p>{{ $footerCopyright }}</p>
            <p>Powered By: <span style="color: var(--ogs-footer-accent, #38bdf8);">{{ $footerPowered }}</span></p>
        </div>
    </div>
</footer>
{{-- OGS FOOTER END --}}
