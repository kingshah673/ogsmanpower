@php
    /*
    ┌─────────────────────────────────────────────────────────────────────┐
    │  BILINGUAL PROFESSIONAL CV  (Template B1 Style)                     │
    │  LEFT  → English (LTR)                                              │
    │  RIGHT → Translated language via $translate->translate() (RTL)      │
    │  Center strip → teal icon circles + vertical line                   │
    │  Format: bilingual_professional_format                               │
    │  DOMPDF-compatible. Table-based layout. DejaVu Sans font.           │
    └─────────────────────────────────────────────────────────────────────┘
    */

    // ── extract job requirement data if available ──────────────────────
    $salary        = optional($jobRequirement)->salary;
    $currency      = optional($jobRequirement)->currency;
    $searchCountry = optional(optional($jobRequirement)->searchcountry)->name;
    $state         = optional(optional($jobRequirement)->state)->name;
    $city          = optional(optional($jobRequirement)->city)->name;

    // ── candidate job title ────────────────────────────────────────────
    $jobTitle = $candidate->job_title
        ?? $candidate->expected_job_title
        ?? optional($candidate->experiences->first())->designation
        ?? 'Professional';

    // ── teal brand color ───────────────────────────────────────────────
    $teal      = '#2d8f8f';
    $tealLight = '#e6f5f5';
    $tealLine  = '#c0e0e0';

    $isRtl = $isRtlLocale ?? resumeIsRtlLocale($resumeLocale ?? 'en');
    $transAlign = $isRtl ? 'right' : 'left';
    $transDir = $isRtl ? 'rtl' : 'ltr';
    $transClass = $isRtl ? 'rtl' : 'ltr';
    $dobFormatted = formatResumeDate($candidate->birth_date, 'd M Y');
    $dobAge = resumeAge($candidate->birth_date);
@endphp
<!DOCTYPE html>
<html lang="{{ $resumeLocale ?? 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ══════════════ BASE ══════════════ */
@page { size: A4 portrait; margin: 6mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: freesans, 'FreeSans', 'DejaVu Sans', Arial, sans-serif;
    font-size: 10.5px;
    color: #222;
    background: #fff;
    line-height: 1.5;
}

.page {
    max-width: 820px;
    margin: 0 auto;
    background: #fff;
    padding: 8px;
    border: 1.5px solid #c0e0e0;
}

/* ══════════════ HEADER TABLE ══════════════ */
.hdr {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

/* Left teal header cell */
.h-en {
    background: #2d8f8f;
    color: #fff;
    padding: 16px 18px 14px;
    width: 40%;
    vertical-align: middle;
}
.h-en .en-name {
    font-size: 17px;
    font-weight: bold;
    letter-spacing: .3px;
    margin: 0 0 3px;
    text-transform: uppercase;
}
.h-en .en-sub {
    font-size: 9.5px;
    color: rgba(255,255,255,.75);
    letter-spacing: .5px;
}

/* Center white header cell */
.h-mid {
    width: 20%;
    background: #fff;
    text-align: center;
    vertical-align: bottom;
    padding: 8px 6px 0;
}
.h-mid-inner {
    display: block;
    text-align: center;
}
.profile-photo {
    width: 80px;
    height: 96px;
    border-radius: 50%;
    border: 3px solid #2d8f8f;
    object-fit: cover;
    display: block;
    margin: 0 auto 4px;
}
.profile-initials {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #e6f5f5;
    border: 3px solid #2d8f8f;
    margin: 0 auto 4px;
    text-align: center;
    line-height: 80px;
    font-size: 24px;
    font-weight: bold;
    color: #2d8f8f;
    display: block;
}
.job-title-center {
    font-size: 10px;
    font-weight: bold;
    color: #2d8f8f;
    text-transform: uppercase;
    letter-spacing: .4px;
    border-bottom: 3px solid #2d8f8f;
    padding-bottom: 6px;
    margin-top: 4px;
}

/* Right teal header cell (translated) */
.h-ar {
    background: #2d8f8f;
    color: #fff;
    padding: 16px 18px 14px;
    width: 40%;
    vertical-align: middle;
}
.h-ar.rtl { text-align: right; direction: rtl; }
.h-ar.ltr { text-align: left; direction: ltr; }
.h-ar .ar-name {
    font-size: 18px;
    font-weight: bold;
    letter-spacing: .3px;
    margin: 0 0 3px;
}
.h-ar .ar-sub {
    font-size: 9.5px;
    color: rgba(255,255,255,.75);
    letter-spacing: .5px;
}

/* ══════════════ BODY TABLE ══════════════ */
.body {
    width: 100%;
    border-collapse: collapse;
}
.body td {
    vertical-align: top;
    border: none;
    padding: 0;
}

/* English column */
.c-en {
    width: 43%;
    padding: 5px 10px 5px 2px;
}

/* Center icon column */
.c-mid {
    width: 52px;
    text-align: center;
    border-left: 1.5px solid #c0e0e0;
    border-right: 1.5px solid #c0e0e0;
    padding: 0 4px;
}

/* Arabic / translated column */
.c-ar {
    width: 43%;
    padding: 5px 2px 5px 10px;
}
.c-ar.rtl {
    text-align: right;
    direction: rtl;
}
.c-ar.ltr {
    text-align: left;
    direction: ltr;
}

/* ── Center icon circle ── */
.ic {
    width: 32px;
    height: 32px;
    background: #2d8f8f;
    border-radius: 50%;
    color: #fff;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    line-height: 32px;
    margin: 6px auto 4px;
    display: block;
}

/* ══════════════ CONTACT INFO ══════════════ */
.ct {
    font-size: 9.5px;
    color: #333;
    padding: 2px 0;
}
.ct .lbl {
    font-weight: bold;
    color: #2d8f8f;
}
.ct-ar {
    font-size: 9.5px;
    color: #333;
    padding: 2px 0;
}
.ct-ar.rtl { direction: rtl; text-align: right; }
.ct-ar.ltr { direction: ltr; text-align: left; }
.ct-ar .lbl {
    font-weight: bold;
    color: #2d8f8f;
}

/* ══════════════ SECTION HEADINGS ══════════════ */
.sh-en {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    color: #2d8f8f;
    letter-spacing: .8px;
    border-bottom: 1.5px solid #2d8f8f;
    padding-bottom: 3px;
    margin: 8px 0 6px;
    text-align: left;
}
.sh-ar {
    font-size: 12px;
    font-weight: bold;
    color: #2d8f8f;
    border-bottom: 1.5px solid #2d8f8f;
    padding-bottom: 3px;
    margin: 8px 0 6px;
}
.sh-ar.rtl { text-align: right; direction: rtl; }
.sh-ar.ltr { text-align: left; direction: ltr; }
.sh-en:first-child, .sh-ar:first-child { margin-top: 0; }

/* ══════════════ PROFESSIONAL SUMMARY ══════════════ */
.bio-en {
    font-size: 10px;
    line-height: 1.75;
    color: #333;
    text-align: justify;
}
.bio-ar {
    font-size: 10px;
    line-height: 1.75;
    color: #333;
}
.bio-ar.rtl { text-align: right; direction: rtl; }
.bio-ar.ltr { text-align: left; direction: ltr; }

/* ══════════════ QUALIFICATIONS / EDUCATION ══════════════ */
.edu-block {
    margin-bottom: 8px;
}
.edu-block-ar {
    margin-bottom: 8px;
}
.edu-block-ar.rtl { direction: rtl; text-align: right; }
.edu-block-ar.ltr { direction: ltr; text-align: left; }
.edu-deg {
    font-size: 10.5px;
    font-weight: bold;
    color: #222;
}
.edu-inst {
    font-size: 10px;
    color: #444;
}
.edu-note {
    font-size: 9.5px;
    color: #666;
}
.edu-year {
    font-size: 9.5px;
    color: #888;
    font-style: italic;
}

/* ══════════════ EXPERIENCE ══════════════ */
.exp-block {
    margin-bottom: 9px;
    padding-bottom: 7px;
    border-bottom: 1px dashed #c0e0e0;
}
.exp-block:last-child { border-bottom: none; }

.exp-block-ar {
    margin-bottom: 9px;
    padding-bottom: 7px;
    border-bottom: 1px dashed #c0e0e0;
}
.exp-block-ar.rtl { direction: rtl; text-align: right; }
.exp-block-ar.ltr { direction: ltr; text-align: left; }
.exp-block-ar:last-child { border-bottom: none; }

.exp-head {
    font-size: 10.5px;
    font-weight: bold;
    color: #222;
    margin-bottom: 1px;
}
.exp-date {
    font-size: 9.5px;
    color: #666;
    font-style: italic;
    margin-bottom: 3px;
}
.exp-bullet {
    font-size: 10px;
    color: #333;
    padding: 1.5px 0 1.5px 12px;
    position: relative;
}

/* ══════════════ CERTIFICATIONS & COURSES ══════════════ */
.cert-block {
    margin-bottom: 6px;
}
.cert-block-ar {
    margin-bottom: 6px;
}
.cert-block-ar.rtl { direction: rtl; text-align: right; }
.cert-block-ar.ltr { direction: ltr; text-align: left; }
.cert-t {
    font-size: 10.5px;
    font-weight: bold;
    color: #222;
}
.cert-s {
    font-size: 9.5px;
    color: #666;
}

/* ══════════════ SKILLS ══════════════ */
.skill-item {
    font-size: 10px;
    color: #333;
    padding: 1.5px 0;
}
.skill-item-ar {
    font-size: 10px;
    color: #333;
    padding: 1.5px 0;
}
.skill-item-ar.rtl { direction: rtl; text-align: right; }
.skill-item-ar.ltr { direction: ltr; text-align: left; }

/* ══════════════ LANGUAGES ══════════════ */
.lang-tbl {
    width: 100%;
    border-collapse: collapse;
}
.lang-tbl td {
    border: none;
    padding: 2px 4px 2px 0;
    font-size: 10px;
    vertical-align: top;
    width: 50%;
}
.lang-tbl-ar {
    width: 100%;
    border-collapse: collapse;
}
.lang-tbl-ar.rtl { direction: rtl; }
.lang-tbl-ar.ltr { direction: ltr; }
.lang-tbl-ar td {
    border: none;
    padding: 2px 0 2px 4px;
    font-size: 10px;
    vertical-align: top;
    width: 50%;
}
.lang-tbl-ar.rtl td { text-align: right; padding: 2px 0 2px 4px; }
.lang-tbl-ar.ltr td { text-align: left; padding: 2px 4px 2px 0; }

/* ══════════════ FOOTER ══════════════ */
.page-footer {
    text-align: center;
    font-size: 9px;
    color: #888;
    margin-top: 8px;
    padding-top: 5px;
    border-top: 1px solid #c0e0e0;
}

/* ══════════════ SMALL SQUARE BULLET ══════════════ */
.sq {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #2d8f8f;
    margin-right: 5px;
    vertical-align: middle;
}
.sq-ar {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #2d8f8f;
    margin-left: 5px;
    vertical-align: middle;
}

/* ══════════════ ATTACHMENT ROW ══════════════ */
.attach-tbl {
    width: 100%;
    border-collapse: collapse;
}
.attach-tbl td {
    border: none;
    padding: 4px;
    text-align: center;
    vertical-align: top;
}
</style>
</head>

<body>
<div class="page">

{{-- ════════════════════════════════════════════════════════
     HEADER — Teal Left | Photo Center | Teal Right (Arabic)
════════════════════════════════════════════════════════ --}}
<table class="hdr">
  <tr>

    {{-- Left: English Name --}}
    <td class="h-en">
      <div class="en-name">{{ strtoupper($candidate->user->name) }}</div>
      <div class="en-sub">
        {{ strtoupper($jobTitle) }}
      </div>
    </td>

    {{-- Center: Photo + Job Title --}}
    <td class="h-mid">
      @if($candidate->photo)
        <img src="{{ resumeImageSrc($candidate->photo) }}"
             alt="{{ $candidate->user->name }}"
             class="profile-photo">
      @else
        <div class="profile-initials">
          {{ strtoupper(substr($candidate->user->name, 0, 2)) }}
        </div>
      @endif
      <div class="job-title-center">{{ strtoupper($jobTitle) }}</div>
    </td>

    {{-- Right: translated column --}}
    <td class="h-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="ar-name" dir="ltr" style="text-align: {{ $transAlign }};">
        {{ $candidate->user->name }}
      </div>
      <div class="ar-sub">
        {{ $resumeLocale === 'en' ? strtoupper($jobTitle) : $translate->translate($jobTitle) }}
      </div>
    </td>

  </tr>
</table>

{{-- ════════════════════════════════════════════════════════
     BODY — English left | Icon center | Arabic right
════════════════════════════════════════════════════════ --}}
<table class="body">

  {{-- ══ ROW 1: CONTACT INFORMATION ══ --}}
  <tr>

    {{-- EN Contact --}}
    <td class="c-en" style="border-top: 2px solid #2d8f8f; padding-top: 8px;">

      @if(isset($candidate->user->phone) && $candidate->user->phone)
      <div class="ct">
        <span class="lbl">Mobile: </span>
        {{ $candidate->user->phone }}
      </div>
      @endif

      @if(isset($candidate->user->email) && $candidate->user->email)
      <div class="ct">
        <span class="lbl">Email: </span>
        {{ $candidate->user->email }}
      </div>
      @endif

      @if(isset($candidate->linkedin) && $candidate->linkedin)
      <div class="ct">
        <span class="lbl">LinkedIn: </span>
        {{ $candidate->linkedin }}
      </div>
      @endif

      <div class="ct">
        <span class="lbl">Address: </span>
        {{ $candidate->district }}, {{ $candidate->region }}
      </div>

      <div class="ct">
        <span class="lbl">Nationality: </span>
        {{ $candidate->country }}
      </div>

      @if($candidate->birth_date)
      <div class="ct">
        <span class="lbl">Date of Birth: </span>
        <span dir="ltr">{{ $dobFormatted }}</span>@if($dobAge) ({{ $dobAge }} yrs)@endif
      </div>
      @endif

      @if($candidate->passport_number)
      <div class="ct">
        <span class="lbl">ID / Passport: </span>
        {{ $candidate->passport_number }}
      </div>
      @endif

      @if($candidate->marital_status)
      <div class="ct">
        <span class="lbl">Marital Status: </span>
        {{ $candidate->marital_status }}
      </div>
      @endif

    </td>

    {{-- Center Icon --}}
    <td class="c-mid" style="border-top: 2px solid #2d8f8f;">
      <div class="ic">&#9733;</div>
    </td>

    {{-- Translated Contact --}}
    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}" style="border-top: 2px solid #2d8f8f; padding-top: 8px;">

      @if(isset($candidate->user->phone) && $candidate->user->phone)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Mobile') }}: </span>
        {{ $candidate->user->phone }}
      </div>
      @endif

      @if(isset($candidate->user->email) && $candidate->user->email)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Email') }}: </span>
        {{ $candidate->user->email }}
      </div>
      @endif

      @if(isset($candidate->linkedin) && $candidate->linkedin)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">LinkedIn: </span>
        {{ $candidate->linkedin }}
      </div>
      @endif

      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Address') }}: </span>
        {{ $candidate->district }}, {{ $candidate->region }}
      </div>

      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Nationality') }}: </span>
        {{ $translate->translate($candidate->country) }}
      </div>

      @if($candidate->birth_date)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Date of Birth') }}: </span>
        <span dir="ltr">{{ $dobFormatted }}</span>@if($dobAge) ({{ $dobAge }} {{ $translate->translate('yrs') }})@endif
      </div>
      @endif

      @if($candidate->passport_number)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('ID / Passport') }}: </span>
        {{ $candidate->passport_number }}
      </div>
      @endif

      @if($candidate->marital_status)
      <div class="ct-ar {{ $transClass }}">
        <span class="lbl">{{ $translate->translate('Marital Status') }}: </span>
        {{ $translate->translate($candidate->marital_status) }}
      </div>
      @endif

    </td>
  </tr>

  {{-- ══ ROW 2: PROFESSIONAL SUMMARY ══ --}}
  @if($candidate->bio)
  <tr>
    <td class="c-en">
      <div class="sh-en">Professional Summary</div>
      <div class="bio-en">{!! $candidate->bio !!}</div>
    </td>

    <td class="c-mid">
      {{-- Person icon ─ Unicode "person silhouette" equivalent --}}
      <div class="ic">&#9786;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Professional Summary') }}</div>
      <div class="bio-ar {{ $transClass }}">{!! $translate->translate($candidate->bio) !!}</div>
    </td>
  </tr>
  @endif

  {{-- ══ ROW 3: QUALIFICATIONS / EDUCATION ══ --}}
  @if($candidate->educations && $candidate->educations->count() > 0)
  <tr>
    <td class="c-en">
      <div class="sh-en">Qualifications</div>
      @foreach($candidate->educations as $edu)
      <div class="edu-block">
        <div class="edu-deg">
          <span class="sq"></span>
          {{ $edu->degree }}
        </div>
        <div class="edu-inst" style="padding-left:11px">
          {{ $edu->level }}
        </div>
        <div class="edu-year" style="padding-left:11px">
          {{ __('Graduation Date') }}: {{ formatResumeYear($edu->year) }}
        </div>
      </div>
      @endforeach
    </td>

    <td class="c-mid">
      {{-- Graduation hat Unicode --}}
      <div class="ic">&#9670;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Qualifications') }}</div>
      @foreach($candidate->educations as $edu)
      <div class="edu-block-ar {{ $transClass }}">
        <div class="edu-deg">
          {{ $translate->translate($edu->degree) }}
          <span class="sq-ar"></span>
        </div>
        <div class="edu-inst" style="padding-right:11px">
          {{ $translate->translate($edu->level) }}
        </div>
        <div class="edu-year" style="padding-{{ $isRtl ? 'right' : 'left' }}:11px">
          {{ $translate->translate('Graduation Date') }}: <span dir="ltr">{{ formatResumeYear($edu->year) }}</span>
        </div>
      </div>
      @endforeach
    </td>
  </tr>
  @endif

  {{-- ══ ROW 4: EXPERIENCE ══ --}}
  @if($candidate->experiences && $candidate->experiences->count() > 0)
  <tr>
    <td class="c-en">
      <div class="sh-en">Experience</div>
      @foreach($candidate->experiences as $exp)
      <div class="exp-block">

        {{-- Title | Company row --}}
        <div class="exp-head">
          <span class="sq"></span>
          <strong>{{ $exp->designation }}</strong>
          &nbsp;|&nbsp;
          {{ $exp->company }}
        </div>

        {{-- Date range --}}
        <div class="exp-date" style="padding-left:11px">
          <span dir="ltr">{{ formatResumeDate($exp->start, 'M Y') }}</span>
          &mdash;
          <span dir="ltr">{{ $exp->currently_working ? 'Present' : formatResumeDate($exp->end, 'M Y') }}</span>
        </div>

        {{-- Bullet descriptions --}}
        @if(!empty($exp->responsibilities))
        <div class="exp-bullet">
          &ndash; {{ \Illuminate\Support\Str::limit(strip_tags($exp->responsibilities), 280) }}
        </div>
        @elseif(!empty($exp->description))
        <div class="exp-bullet">
          &ndash; {{ \Illuminate\Support\Str::limit(strip_tags($exp->description), 280) }}
        </div>
        @endif

      </div>
      @endforeach
    </td>

    <td class="c-mid">
      {{-- Briefcase / work icon --}}
      <div class="ic">&#9881;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Experience') }}</div>
      @foreach($candidate->experiences as $exp)
      <div class="exp-block-ar {{ $transClass }}">

        <div class="exp-head" style="text-align:{{ $transAlign }}">
          {{ $translate->translate($exp->company) }}
          &nbsp;|&nbsp;
          <strong>{{ $translate->translate($exp->designation) }}</strong>
          <span class="sq-ar"></span>
        </div>

        <div class="exp-date" style="padding-{{ $isRtl ? 'right' : 'left' }}:11px">
          <span dir="ltr">{{ formatResumeDate($exp->start, 'M Y') }}</span>
          &mdash;
          <span dir="ltr">{{ $exp->currently_working ? $translate->translate('Present') : formatResumeDate($exp->end, 'M Y') }}</span>
        </div>

        @if(!empty($exp->responsibilities))
        <div style="font-size:10px;color:#333;padding:1.5px 11px 1.5px 0">
          &ndash; {!! $translate->translate(\Illuminate\Support\Str::limit(strip_tags($exp->responsibilities), 280)) !!}
        </div>
        @elseif(!empty($exp->description))
        <div style="font-size:10px;color:#333;padding:1.5px 11px 1.5px 0">
          &ndash; {!! $translate->translate(\Illuminate\Support\Str::limit(strip_tags($exp->description), 280)) !!}
        </div>
        @endif

      </div>
      @endforeach
    </td>
  </tr>
  @endif

  {{-- ══ ROW 5: CERTIFICATIONS & COURSES ══ --}}
  @if($candidate->attributes && $candidate->attributes->count() > 0)
  <tr>
    <td class="c-en">
      <div class="sh-en">Certifications &amp; Courses</div>
      @foreach($candidate->attributes as $at)
      <div class="cert-block">
        <div class="cert-t">
          <span class="sq"></span>
          {{ $at->attribute_name }}
        </div>
        <div class="cert-s" style="padding-left:11px">
          {{ $at->attribute_value }}
        </div>
      </div>
      @endforeach
    </td>

    <td class="c-mid">
      {{-- Certificate icon --}}
      <div class="ic">&#10003;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Certifications & Courses') }}</div>
      @foreach($candidate->attributes as $at)
      <div class="cert-block-ar {{ $transClass }}">
        <div class="cert-t">
          {{ $translate->translate($at->attribute_name) }}
          <span class="sq-ar"></span>
        </div>
        <div class="cert-s" style="padding-right:11px">
          {{ $translate->translate($at->attribute_value) }}
        </div>
      </div>
      @endforeach
    </td>
  </tr>
  @endif

  {{-- ══ ROW 6: SKILLS ══ --}}
  @if($candidate->skills && $candidate->skills->count() > 0)
  <tr>
    <td class="c-en">
      <div class="sh-en">Skills</div>
      @foreach($candidate->skills as $skill)
      <div class="skill-item">
        <span class="sq"></span>
        {{ $skill->name }}
      </div>
      @endforeach
    </td>

    <td class="c-mid">
      {{-- Skills gear icon --}}
      <div class="ic">&#9670;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Skills') }}</div>
      @foreach($candidate->skills as $skill)
      <div class="skill-item-ar {{ $transClass }}">
        {{ $translate->translate($skill->name) }}
        <span class="sq-ar"></span>
      </div>
      @endforeach
    </td>
  </tr>
  @endif

  {{-- ══ ROW 7: LANGUAGES ══ --}}
  @if($candidate->languages && $candidate->languages->count() > 0)
  <tr>
    <td class="c-en">
      <div class="sh-en">Languages</div>
      <table class="lang-tbl">
        <tr>
          @foreach($candidate->languages as $lang)
          <td>
            <span class="sq"></span>
            <strong>{{ $lang->name }}:</strong>
            {{ $lang->level ?? $translate->translate('Proficient') }}
          </td>
          @endforeach
        </tr>
      </table>
    </td>

    <td class="c-mid">
      {{-- Globe / language icon --}}
      <div class="ic">&#9733;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Languages') }}</div>
      <table class="lang-tbl-ar {{ $transClass }}">
        <tr>
          @foreach($candidate->languages as $lang)
          <td>
            <strong>{{ $translate->translate($lang->name) }}:</strong>
            {{ $lang->level ? $translate->translate($lang->level) : $translate->translate('Proficient') }}
            <span class="sq-ar"></span>
          </td>
          @endforeach
        </tr>
      </table>
    </td>
  </tr>
  @endif

  {{-- ══ ROW 8: PASSPORT INFO (optional — shown when available) ══ --}}
  @if($candidate->passport_number)
  <tr>
    <td class="c-en">
      <div class="sh-en">Passport &amp; Documents</div>
      <table style="width:100%;border-collapse:collapse">
        <tr>
          <td style="font-size:10px;padding:2px 6px 2px 0;border:none;width:50%">
            <span style="font-weight:bold;color:#2d8f8f">Passport No.:</span><br>
            {{ $candidate->passport_number }}
          </td>
          <td style="font-size:10px;padding:2px 0;border:none;width:50%">
            <span style="font-weight:bold;color:#2d8f8f">CNIC:</span><br>
            {{ $candidate->cnic_number }}
          </td>
        </tr>
        <tr>
          <td style="font-size:10px;padding:2px 6px 2px 0;border:none">
            <span style="font-weight:bold;color:#2d8f8f">Issue Date:</span><br>
            <span dir="ltr">{{ formatResumeDate($candidate->passport_issue_date, 'd M Y') }}</span>
          </td>
          <td style="font-size:10px;padding:2px 0;border:none">
            <span style="font-weight:bold;color:#2d8f8f">Expiry Date:</span><br>
            <span dir="ltr">{{ formatResumeDate($candidate->passport_expiry_date, 'd M Y') }}</span>
          </td>
        </tr>
        @if($candidate->place_of_issue)
        <tr>
          <td colspan="2" style="font-size:10px;padding:2px 0;border:none">
            <span style="font-weight:bold;color:#2d8f8f">Place of Issue:</span>
            {{ $candidate->place_of_issue }}
          </td>
        </tr>
        @endif
      </table>
    </td>

    <td class="c-mid">
      <div class="ic">&#9993;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Passport & Documents') }}</div>
      <table style="width:100%;border-collapse:collapse;direction:{{ $transDir }}">
        <tr>
          <td style="font-size:10px;padding:2px 0 2px 6px;border:none;text-align:{{ $transAlign }};width:50%">
            <span style="font-weight:bold;color:#2d8f8f">{{ $translate->translate('Passport No.') }}:</span><br>
            {{ $candidate->passport_number }}
          </td>
          <td style="font-size:10px;padding:2px 0;border:none;text-align:{{ $transAlign }};width:50%">
            <span style="font-weight:bold;color:#2d8f8f">{{ $translate->translate('CNIC') }}:</span><br>
            {{ $candidate->cnic_number }}
          </td>
        </tr>
        <tr>
          <td style="font-size:10px;padding:2px 0 2px 6px;border:none;text-align:{{ $transAlign }}">
            <span style="font-weight:bold;color:#2d8f8f">{{ $translate->translate('Issue Date') }}:</span><br>
            <span dir="ltr">{{ formatResumeDate($candidate->passport_issue_date, 'd M Y') }}</span>
          </td>
          <td style="font-size:10px;padding:2px 0;border:none;text-align:{{ $transAlign }};width:50%">
            <span style="font-weight:bold;color:#2d8f8f">{{ $translate->translate('Expiry Date') }}:</span><br>
            <span dir="ltr">{{ formatResumeDate($candidate->passport_expiry_date, 'd M Y') }}</span>
          </td>
        </tr>
        @if($candidate->place_of_issue)
        <tr>
          <td colspan="2" style="font-size:10px;padding:2px 0;border:none;text-align:{{ $transAlign }}">
            <span style="font-weight:bold;color:#2d8f8f">{{ $translate->translate('Place of Issue') }}:</span>
            {{ $candidate->place_of_issue }}
          </td>
        </tr>
        @endif
      </table>
    </td>
  </tr>
  @endif

  {{-- ══ ROW 9: ATTACHMENTS (optional) ══ --}}
  @if(isset($attachments) && ($attachments->license_image || $attachments->passport_image))
  <tr>
    <td class="c-en">
      <div class="sh-en">Attachments</div>
      @if($attachments->license_image)
        <div style="margin-bottom:4px">
          <img src="{{ resumeImageSrc('storage/candidates/'.$attachments->license_image) }}"
               style="width:150px;height:95px;border:1.5px solid #c0e0e0;object-fit:cover">
          <div style="font-size:9px;color:#888;margin-top:2px">License</div>
        </div>
      @endif
    </td>

    <td class="c-mid">
      <div class="ic">&#9993;</div>
    </td>

    <td class="c-ar {{ $transClass }}" lang="{{ $resumeLocale }}">
      <div class="sh-ar {{ $transClass }}">{{ $translate->translate('Attachments') }}</div>
      @if($attachments->passport_image)
        <div style="margin-bottom:4px;text-align:{{ $transAlign }}">
          <img src="{{ resumeImageSrc('storage/candidates/'.$attachments->passport_image) }}"
               style="width:150px;height:95px;border:1.5px solid #c0e0e0;object-fit:cover">
          <div style="font-size:9px;color:#888;margin-top:2px;text-align:{{ $transAlign }}">
            {{ $translate->translate('Passport') }}
          </div>
        </div>
      @endif
    </td>
  </tr>
  @endif

</table>

{{-- ════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════ --}}
<div class="page-footer">
  OGS Manpower &mdash; Lic No. 2978 Pakistan &mdash;
  {{ $translate->translate('All rights reserved') }}
</div>

</div>{{-- /page --}}
</body>
</html>
