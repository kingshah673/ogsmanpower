@extends('frontend.layouts.app')

@section('title', 'About OGS Manpower')

@section('content')
@section('main')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

/* ===== GLOBAL ===== */
body { font-family: 'Segoe UI', sans-serif; }

/* ===== HERO ===== */
.hero {
    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)),
                url('../icons/banner.png');
    background-size: cover;
    padding: 140px 0;
    color: #fff;
}

.hero h1 {
    font-size: 48px;
    font-weight: 700;
}

.hero p {
    font-size: 18px;
}

.btn-custom {
    padding: 12px 25px;
    border-radius: 30px;
    font-weight: 600;
}

/* ===== SECTION TITLE ===== */
.section-title {
    text-align: center;
    font-weight: 700;
    margin-bottom: 50px;
}

/* ===== CARDS ===== */
.card-modern {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    transition: 0.3s;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.card-modern:hover {
    transform: translateY(-5px);
}

/* ===== VIDEO ===== */
.video-section {
    background: #0b2a4a;
    padding: 80px 0;
    color: #fff;
}

.video-box {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
}
.video-box img {
    width: 100%;
}
.play-btn {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: red;
    color: white;
    border-radius: 50%;
    padding: 12px 18px;
}

/* ===== CTA ===== */
.cta {
    background: linear-gradient(90deg,#7a1f1f,#0b2a4a);
    color: #fff;
    padding: 80px 0;
}

/* ===== COUNTER ===== */
.counter {
    font-size: 30px;
    font-weight: bold;
}
/*====icons====*/
.industries-section {
    background: #f8f9fa;
}

.industry-card {
    background: #fff;
    padding: 20px 10px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.industry-card img {
    width: 64px;
    height: 64px;
    object-fit: contain;
    margin-bottom: 10px;
    border-radius: 12px;
}

.industry-card h6 {
    font-size: 13px;
    font-weight: 600;
}

.industry-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
/*===whychoose==*/
.why-section {
    background: #f8f9fa;
}

.why-card {
    background: #fff;
    padding: 20px 10px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    height: 100%;
}

.why-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.icon-box {
    width: 60px;
    height: 60px;
    margin: 0 auto 10px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-box img {
    width: 30px;
    height: 30px;
}

.why-card p {
    font-size: 13px;
    font-weight: 600;
    margin: 0;
}
/*======video=====*/
.video-section {
    background: url('/icons/video-bg.png') center/cover no-repeat;
    position: relative;
    padding: 80px 0;
}

/* DARK OVERLAY */
.video-section .overlay {
    background: rgba(11, 42, 74, 0.85); /* dark blue overlay */
    padding: 80px 0;
}

/* VIDEO CARD */
.video-card {
    border-radius: 15px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    transition: 0.3s;
}

.video-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.6);
}

.video-card iframe {
    border: none;
}
/*=======counter======*/
.counter-section {
    background: #ffffff;
}

.counter-card {
    background: #fff;
    padding: 30px 20px;
    border-radius: 15px;
    transition: 0.3s;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.counter-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.counter-card .icon {
    font-size: 30px;
    color: #0b2a4a;
    margin-bottom: 10px;
}

.counter-card h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 5px 0;
}

.counter-card p {
    font-size: 14px;
    color: #666;
    margin: 0;
}
/*======global=====*/
.global-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* LEFT SIDE */
.portal-img {
    width: 100%;
    border-radius: 10px;
}

.portal-box ul {
    list-style: none;
    padding: 0;
    font-size: 14px;
}

.portal-box ul li {
    margin-bottom: 6px;
}

/* MAP AREA */
.map-area {
    position: relative;
}

.map-bg {
    width: 100%;
    border-radius: 15px;
    opacity: 0.95;
}

/* PIN BASE */
.map-pin {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* DOT */
.pin-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
}

/* COLORS */
.green { background: #2e7d32; }
.red { background: #c62828; }
.dark { background: #1b5e20; }

/* LABEL */
.pin-label {
    background: #fff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    font-weight: 600;
}

/* POSITIONS (ADJUST IF NEEDED) */
.uae {
    top: 45%;
    left: 60%;
}

.pakistan {
    top: 43%;
    left: 65%;
}

.uk {
    top: 35%;
    left: 50%;
}
.social-connect {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.25rem 1.75rem;
    max-width: 920px;
    margin: 0 auto;
}
.social-connect-link {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: .55rem;
    text-decoration: none;
    color: #0b2a4a;
    min-width: 88px;
    transition: transform .25s ease, color .25s ease;
}
.social-connect-link:hover {
    transform: translateY(-4px);
    color: #C9A84C;
    text-decoration: none;
}
.social-connect-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(145deg, #f4f7fb, #e8eef5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}
.social-connect-icon img {
    width: 28px;
    height: 28px;
    object-fit: contain;
}
.social-connect-link span {
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .02em;
}
.social-card {
    display: block;
    background: #fff;
    padding: 20px 10px;
    border-radius: 14px;
    text-align: center;
    transition: 0.3s;
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    text-decoration: none;
    color: inherit;
}

.icon-box {
    width: 60px;
    height: 60px;
    margin: 0 auto 12px;
    background: #eef3f8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-box img {
    width: 30px;
    height: 30px;
}

.social-card h6 {
    font-size: 13px;
    font-weight: 600;
}

.social-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.12);
}

.social-card:hover .icon-box {
    background: #0b2a4a;
}

/* ===== WHY SECTION ===== */
.why-section{background:#0A1628;padding:80px 0;color:#fff;}
.section-label{color:#C9A84C;font-size:12px;letter-spacing:2px;text-transform:uppercase;}
.section-title{font-size:36px;margin-bottom:10px;}
.cards-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px;}

.feature-card{
    background:rgba(255,255,255,0.05);
    padding:20px;
    text-align:center;
    cursor:pointer;
    border:1px solid rgba(255,255,255,0.1);
    transition:0.3s;
}
.feature-card:hover{transform:translateY(-5px);border-color:#C9A84C;}
.fc-icon{font-size:30px;}
.fc-title{font-weight:bold;margin-top:10px;}
.fc-teaser{font-size:13px;color:#ccc;margin-top:5px;}
.fc-click{font-size:12px;color:#C9A84C;margin-top:8px;}

.modal-overlay{
    position:fixed;
    top:0;left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.85);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:999;
    padding:20px;
}

.modal-overlay.open{
    display:flex;
}

.modal-box{
    background:#122040;
    width:100%;
    max-width:700px;
    max-height:90vh;   /* 🔥 IMPORTANT */
    overflow-y:auto;   /* 🔥 ENABLE SCROLL */
    padding:25px;
    color:#fff;
    position:relative;
    border-radius:6px;
}

.modal-box::-webkit-scrollbar {
    width:6px;
}
.modal-box::-webkit-scrollbar-thumb {
    background:#C9A84C;
    border-radius:10px;
}
@media(max-width:600px){
  .cards-grid,.metrics-grid,.ind-grid,.social-grid{grid-template-columns:1fr;}
  section{padding:3.5rem 4%;}
}


</style>
@php
    $cfg = collect($aboutConfig ?? []);
@endphp
<!-- HERO (Admin → About → Hero) -->
<section class="hero text-center">
    <div class="container">
        @if (!empty($aboutHero?->badge_text))
            <div class="mb-2 text-uppercase small fw-bold" style="letter-spacing:.08em;">{{ $aboutHero->badge_text }}</div>
        @endif
        <h1>{!! $aboutHero?->headline ?: 'Reliable Global Manpower Solutions' !!}</h1>
        @if (!empty($aboutHero?->subheadline))
            <p>{{ $aboutHero->subheadline }}</p>
        @endif

        <div class="mt-4">
            <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn btn-success btn-custom">Register as Seeker</a>
            <a href="{{ route('register', ['type' => 'employer']) }}" class="btn btn-danger btn-custom">Register as Employer</a>
        </div>

        @php
            $pills = array_values(array_filter([
                $aboutHero->pill_1 ?? null,
                $aboutHero->pill_2 ?? null,
                $aboutHero->pill_3 ?? null,
            ]));
        @endphp
        @if (count($pills))
            <p class="mt-4">✔ {{ implode(' | ✔ ', $pills) }}</p>
        @endif
    </div>
</section>

<!-- ABOUT / STORY (Admin → About → Story) -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">{{ $aboutStory->section_label ?? 'About OGS Manpower' }}</h2>

        <div class="row align-items-center">
            <div class="col-md-6">
                <img src="{{ asset('images/about-ogs.png') }}" class="img-fluid rounded" alt="About OGS Manpower">
            </div>
            <div class="col-md-6">
                @if (!empty($aboutStory?->headline))
                    <h4 class="mb-3">{{ $aboutStory->headline }}</h4>
                @endif
                @if (!empty($aboutStory?->quote))
                    <p class="fst-italic text-muted">“{{ $aboutStory->quote }}”</p>
                @endif
                @foreach (['body_1','body_2','body_3'] as $bodyKey)
                    @if (!empty($aboutStory?->{$bodyKey}))
                        <p>{!! nl2br(e($aboutStory->{$bodyKey})) !!}</p>
                    @endif
                @endforeach
                @if (!empty($aboutStory?->mission))
                    <div class="mt-3">
                        <strong>Our mission is to:</strong>
                        <div style="margin-left:30px;margin-top:8px;">
                            <strong>
                                @foreach (preg_split('/\r\n|\r|\n/', $aboutStory->mission) as $line)
                                    @if (trim($line) !== '')
                                        ✔ {{ ltrim(trim($line), "✔ ") }}<br/>
                                    @endif
                                @endforeach
                            </strong>
                        </div>
                    </div>
                @endif
                @if (!empty($aboutStory?->license_text))
                    <p class="mt-3"><strong>{{ $aboutStory->license_text }}</strong></p>
                @endif
                @if (!empty($aboutStory?->card_1_num) || !empty($aboutStory?->card_2_num))
                    <div class="row mt-3">
                        @if (!empty($aboutStory?->card_1_num))
                            <div class="col-md-6 mb-2">
                                <strong>{{ $aboutStory->card_1_num }} {{ $aboutStory->card_1_lbl }}</strong>
                                <div class="small text-muted">{{ $aboutStory->card_1_desc }}</div>
                            </div>
                        @endif
                        @if (!empty($aboutStory?->card_2_num))
                            <div class="col-md-6 mb-2">
                                <strong>{{ $aboutStory->card_2_num }} {{ $aboutStory->card_2_lbl }}</strong>
                                <div class="small text-muted">{{ $aboutStory->card_2_desc }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- WHY SECTION -->
<section class="why-section">
<div class="container text-center">

<div class="section-label">{{ $cfg['features_label'] ?? 'Why Choose OGS' }}</div>
<h2 class="section-title">{{ $cfg['features_title'] ?? 'Why Employers Trust OGS' }}</h2>
<p>{{ $cfg['features_intro'] ?? 'Click any card below to explore our strengths in detail.' }}</p>

<div class="cards-grid">
@php
    $features = collect($aboutFeatures ?? []);
    if ($features->isEmpty()) {
        $features = collect([
            (object)['id'=>1,'icon_emoji'=>'👔','title'=>'CEO Leadership','teaser'=>'25+ years in HR, recruitment, AI, and global workforce development.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Employer →'],
            (object)['id'=>2,'icon_emoji'=>'🏆','title'=>'15+ Years Experience','teaser'=>'OGS Group has been delivering complete HR solutions globally since 2010.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Employer →'],
            (object)['id'=>3,'icon_emoji'=>'🌍','title'=>'100+ Clients','teaser'=>'Trusted by 100+ clients across the Gulf region and international markets.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Employer →'],
            (object)['id'=>4,'icon_emoji'=>'⭐','title'=>'Strong Reputation','teaser'=>'Years of credibility and recognition in the global HR business community.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Employer →'],
            (object)['id'=>5,'icon_emoji'=>'💻','title'=>'Job Portal','teaser'=>'AI-powered recruitment platform with smart filters and full lifecycle tracking.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Employer →'],
            (object)['id'=>6,'icon_emoji'=>'📂','title'=>'Verified CV Bank','teaser'=>'Verified, deployment-ready candidates with zero documentation errors.','modal_body'=>'','badge_tags'=>'','cta_text'=>'Register as Seeker →'],
        ]);
    }
@endphp

@foreach($features as $i => $f)
<div class="feature-card" onclick="openModal('feature-{{ $f->id }}')" @if(!empty($f->icon_bg_color)) style="--fc-bg: {{ $f->icon_bg_color }}" @endif>
<span>{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
<div class="fc-icon">{{ $f->icon_emoji }}</div>
<div class="fc-title">{{ $f->title }}</div>
<div class="fc-teaser">{{ $f->teaser }}</div>
<div class="fc-click">Click to Learn More →</div>
</div>
@endforeach

</div>
</div>
</section>

<!-- VIDEO TESTIMONIALS WITH BACKGROUND -->
<section class="video-section text-center">
    <div class="overlay">
        <div class="container">
            <h2 class="section-title text-white mb-5">{{ $cfg['journey_title'] ?? 'OGS Journey' }}</h2>

            <div class="row">
                @forelse(($aboutVideos ?? collect()) as $video)
                    @php
                        $rawUrl = $video->video_url ?? '';
                        $embed = $rawUrl;
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]+)/', $rawUrl, $m)) {
                            $embed = 'https://www.youtube.com/embed/' . $m[1];
                        } elseif (($video->video_type ?? '') === 'youtube' && !str_contains($rawUrl, 'embed') && !str_contains($rawUrl, 'http')) {
                            $embed = 'https://www.youtube.com/embed/' . $rawUrl;
                        }
                    @endphp
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="video-card">
                            @if (!empty($video->title))
                                <h6 class="text-white mb-2">{{ $video->title }}</h6>
                            @endif
                            @if (!empty($video->description))
                                <p class="text-white-50 small mb-2">{{ $video->description }}</p>
                            @endif
                            <div class="ratio ratio-16x9">
                                @if (($video->video_type ?? 'youtube') === 'file' || str_contains($rawUrl, '/storage/'))
                                    <video controls src="{{ str_starts_with($rawUrl, 'http') ? $rawUrl : asset('storage/'.ltrim($rawUrl, '/')) }}"></video>
                                @else
                                    <iframe src="{{ $embed }}" allowfullscreen loading="lazy" title="{{ $video->title ?? 'OGS Journey' }}"></iframe>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-white-50">Journey videos will appear here once added from the Admin Panel.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<!-- PROFESSIONAL COUNTER SECTION -->
<section class="counter-section py-5">
    <div class="container">
        <div class="row text-center">
            @php
                $stats = collect($aboutMetrics ?? []);
                if ($stats->isEmpty()) {
                    $stats = collect([
                        (object)['icon'=>'📅','value'=>'15+','label'=>'Years Experience'],
                        (object)['icon'=>'👷','value'=>'10K+','label'=>'Workers Deployed'],
                        (object)['icon'=>'🏢','value'=>'200+','label'=>'Clients'],
                        (object)['icon'=>'🌍','value'=>'5+','label'=>'Countries Served'],
                    ]);
                }
            @endphp

            @foreach($stats as $item)
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="counter-card">
                    <div class="icon" style="font-size:1.8rem;line-height:1;">{{ $item->icon }}</div>
                    <h3>{{ $item->value }}</h3>
                    <p>{{ $item->label }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- GLOBAL PRESENCE CARD (PIXEL PERFECT) -->
<section class="py-5">
    <div class="container">

        <h2 class="text-center fw-bold mb-4">{{ $cfg['global_title'] ?? 'Our Global Presence' }}</h2>

        <div class="global-card">

            <div class="row align-items-center">

                <!-- LEFT CONTENT -->
                <div class="col-ng-5" style="width:25%;">
                    <div class="portal-box">

                        <img src="{{ asset('images/team.png') }}" class="portal-img">

                        <h4 class="mt-3">
                            {{ $cfg['portal_title'] ?? 'Our Candidate Portal' }}
                        </h4>

                        <p>{{ $cfg['portal_subtitle'] ?? 'Find Pre-Screened Candidates Instantly' }}</p>

                        <ul>
                            @foreach (preg_split('/\r\n|\r|\n/', $cfg['portal_bullets'] ?? "Search & Filter Profiles\nVerified CVs\nVideo Interviews") as $bullet)
                                @if (trim($bullet) !== '')
                                    <li>✔ {{ ltrim(trim($bullet), "✔ ") }}</li>
                                @endif
                            @endforeach
                        </ul>

                        <a href="{{ url($cfg['portal_btn_url'] ?? '/candidates') }}" class="btn btn-primary">{{ $cfg['portal_btn_text'] ?? 'Explore Candidates' }}</a>

                    </div>
                </div>

                <!-- RIGHT MAP -->
                <div class="col-ng-7" style="width:75%;">
                    <div class="map-area">

                        <!-- BACKGROUND IMAGE -->
                        <img src="{{ asset('images/global-bg.png') }}" class="map-bg">

                        @php
                            $offices = collect($aboutOffices ?? []);
                            if ($offices->isEmpty()) {
                                $offices = collect([
                                    (object)['country'=>'UAE','city'=>'Dubai','flag'=>'🇦🇪'],
                                    (object)['country'=>'Pakistan','city'=>'Islamabad','flag'=>'🇵🇰'],
                                    (object)['country'=>'United Kingdom','city'=>'London','flag'=>'🇬🇧'],
                                ]);
                            }
                            $pinClasses = ['uae','pakistan','uk','uae','pakistan','uk'];
                            $dotClasses = ['green','dark','red','green','dark','red'];
                        @endphp
                        @foreach($offices->take(3) as $oi => $office)
                            <div class="map-pin {{ $pinClasses[$oi] ?? 'uae' }}">
                                <span class="pin-dot {{ $dotClasses[$oi] ?? 'green' }}"></span>
                                <div class="pin-label">{{ $office->flag ?? '' }} {{ $office->country }}@if(!empty($office->city)) · {{ $office->city }}@endif</div>
                            </div>
                        @endforeach

                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
<!-- INDUSTRIES (SMALL ICON STYLE) -->
<section class="industries2-section py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-5">{{ $cfg['industries_title'] ?? 'Industries We Serve' }}</h2>

        <div class="row justify-content-center">
            @php
                $industries = collect($aboutIndustries ?? []);
                if ($industries->isEmpty()) {
                    $industries = collect([
                        (object)['name'=>'Construction','icon'=>'/icons/construction.png'],
                        (object)['name'=>'Oil & Gas','icon'=>'/icons/oil-gas.png'],
                        (object)['name'=>'Hospitality','icon'=>'/icons/hospitality.png'],
                        (object)['name'=>'Facility','icon'=>'/icons/facility.png'],
                        (object)['name'=>'Security','icon'=>'/icons/security.png'],
                        (object)['name'=>'Engineering','icon'=>'/icons/engineering.png'],
                    ]);
                }
            @endphp

            @foreach($industries as $ind)
            <div class="col-lg-2 col-md-4 col-6 mb-4">
                <div class="industry-card">
                    @php
                        $icon = (string) ($ind->icon ?? '');
                        $iconIsImage = $icon !== '' && (str_contains($icon, '/') || preg_match('/\.(png|jpe?g|webp|svg)$/i', $icon));
                        $iconSrc = $iconIsImage
                            ? (str_starts_with($icon, 'http') ? $icon : asset(ltrim($icon, '/')))
                            : null;
                    @endphp
                    @if ($iconSrc)
                        <img src="{{ $iconSrc }}" alt="{{ $ind->name }}">
                    @else
                        <div style="font-size:2rem;line-height:1;margin-bottom:.5rem;">{{ $icon ?: '🏭' }}</div>
                    @endif
                    <h6>{{ $ind->name }}</h6>
                    @if (!empty($ind->description))
                        <small class="text-muted d-block">{{ $ind->description }}</small>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
<!-- CTA -->
<!-- SOCIAL MEDIA (OGS STYLE) -->
<section class="industries-section py-5">
    <div class="container text-center">

        <h2 class="section-title mb-3">{{ $cfg['connect_title'] ?? 'Connect With OGS Manpower' }}</h2>
        <p class="text-muted mb-5 mx-auto" style="max-width:520px;">{{ $cfg['connect_subtitle'] ?? 'Follow OGS across our channels for updates, opportunities, and community news.' }}</p>

        @php
            $fallbackSocials = [
                ['platform'=>'WhatsApp Group','icon'=>'💬','url'=>'https://chat.whatsapp.com/G0YIKjgkSy90j9bIN1LuDU?mode=gi_t','img'=>'icons/social/whatsapp.png'],
                ['platform'=>'Facebook','icon'=>'📘','url'=>'https://www.facebook.com/ogs.official','img'=>'icons/social/facebook.png'],
                ['platform'=>'TikTok','icon'=>'🎵','url'=>'https://www.tiktok.com/@ogs.manpower','img'=>'icons/social/tiktok.png'],
                ['platform'=>'Instagram','icon'=>'📸','url'=>'https://www.instagram.com/ogsmanpower','img'=>'icons/social/instagram.png'],
                ['platform'=>'WhatsApp Channel','icon'=>'💬','url'=>'https://whatsapp.com/channel/0029VaCbduB9Gv7RhaugxS0Z','img'=>'icons/social/whatsapp.png'],
                ['platform'=>'X (Twitter)','icon'=>'🐦','url'=>'https://x.com/ogsmanpower','img'=>'icons/social/twitter.png'],
                ['platform'=>'LinkedIn','icon'=>'💼','url'=>'https://www.linkedin.com/company/ogsmanpower/','img'=>'icons/social/linkedin.png'],
                ['platform'=>'YouTube','icon'=>'▶️','url'=>'https://youtube.com/@ogsgroupofficial','img'=>'icons/social/youtube.png'],
            ];
            $cmsSocials = collect($aboutSocials ?? [])->filter(fn ($s) => !empty($s->url) && $s->url !== '#');
            $socialIconMap = [
                'facebook' => 'icons/social/facebook.png',
                'instagram' => 'icons/social/instagram.png',
                'linkedin' => 'icons/social/linkedin.png',
                'youtube' => 'icons/social/youtube.png',
                'twitter' => 'icons/social/twitter.png',
                'x' => 'icons/social/twitter.png',
                'whatsapp' => 'icons/social/whatsapp.png',
                'tiktok' => 'icons/social/tiktok.png',
            ];
        @endphp

        <div class="social-connect">
            @if ($cmsSocials->count())
                @foreach ($cmsSocials as $item)
                    @php
                        $plat = strtolower((string) ($item->platform ?? ''));
                        $img = null;
                        foreach ($socialIconMap as $needle => $path) {
                            if (str_contains($plat, $needle)) {
                                $img = $path;
                                break;
                            }
                        }
                    @endphp
                    <a href="{{ $item->url }}" target="_blank" rel="noopener" class="social-connect-link">
                        <div class="social-connect-icon">
                            @if ($img)
                                <img src="{{ asset($img) }}" alt="">
                            @elseif (!empty($item->icon) && $item->icon !== $item->platform)
                                {{ $item->icon }}
                            @else
                                🔗
                            @endif
                        </div>
                        <span>{{ $item->platform }}</span>
                    </a>
                @endforeach
            @else
                @foreach ($fallbackSocials as $item)
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="social-connect-link">
                        <div class="social-connect-icon">
                            <img src="{{ asset($item['img']) }}" alt="{{ $item['platform'] }}">
                        </div>
                        <span>{{ $item['platform'] }}</span>
                    </a>
                @endforeach
            @endif
        </div>

    </div>
</section>
<section class="cta">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-md-7">
                <h4 class="mb-3">{{ $cfg['join_title'] ?? 'Join OGS Manpower' }}</h4>
                <p class="mb-4">{{ $cfg['join_text'] ?? 'Create your account as a job seeker or employer and get started in minutes.' }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn btn-success">Register as Seeker</a>
                    <a href="{{ route('register', ['type' => 'employer']) }}" class="btn btn-danger">Register as Employer</a>
                </div>
            </div>

            <div class="col-md-5 text-center">
                <h4>CEO Message</h4>
                @if (!empty($aboutCeo?->name))
                    <p class="small text-muted mb-2">{{ $aboutCeo->name }}@if(!empty($aboutCeo->title)) — {{ $aboutCeo->title }}@endif@if(!empty($aboutCeo->experience)) · {{ $aboutCeo->experience }} Years@endif</p>
                @endif
                <p style="text-align:justify;">
                    {{ $aboutCeo->quote ?? 'At OGS Manpower, we are committed to delivering reliable, skilled, and pre-screened manpower solutions to global employers. With over 15 years of experience and operations across Pakistan, UAE, and the UK, we focus on quality, transparency, and long-term partnerships. Our goal is to support business growth while creating better career opportunities worldwide.' }}
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ═══════ MODALS (from About CMS Features) ═══════ -->
@foreach(($features ?? collect()) as $f)
@php
    $modalId = 'feature-'.$f->id;
    $tags = array_filter(array_map('trim', explode(',', (string) ($f->badge_tags ?? ''))));
    $cta = $f->cta_text ?: 'Register Now →';
    $ctaUrl = (stripos($cta, 'seeker') !== false || stripos($cta, 'candidate') !== false)
        ? route('register', ['type' => 'seeker'])
        : route('register', ['type' => 'employer']);
@endphp
<div class="modal-overlay" id="{{ $modalId }}" onclick="closeOnOverlay(event,'{{ $modalId }}')">
  <div class="modal-box">
    <div class="modal-header">
      <div style="display:flex;gap:1rem;align-items:flex-start;">
        <span class="modal-icon-lg">{{ $f->icon_emoji }}</span>
        <div>
          <h3>{{ $f->title }}</h3>
          <p class="mh-sub">{{ $f->teaser }}</p>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('{{ $modalId }}')">✕</button>
    </div>
    <div class="modal-body">
      @if (!empty($f->modal_body))
        {!! $f->modal_body !!}
      @else
        <p>{{ $f->teaser }}</p>
      @endif
      @if (count($tags))
        <div>
          @foreach($tags as $tag)
            <span class="modal-tag">{{ $tag }}</span>
          @endforeach
        </div>
      @endif
      <button class="modal-cta" onclick="window.location.href='{{ $ctaUrl }}'">{{ $cta }}</button>
    </div>
  </div>
</div>
@endforeach
<script>
function openModal(id){
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id){
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('open');
    document.body.style.overflow = 'auto';
}

function closeOnOverlay(e, id){
    if (e.target && e.target.id === id) {
        closeModal(id);
    }
}
</script>

@endsection
@endsection