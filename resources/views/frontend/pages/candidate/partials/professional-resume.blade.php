{{-- Shared professional resume layout (browser + PDF) --}}
@php
    $compactPdf = !empty($compactPdf);
    $fullName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''))
        ?: ($candidate->user->name ?? 'Candidate');
    $headline = $candidate->title ?? (optional($candidate->profession)->name);
    $phone = $contact->phone ?? ($candidate->whatsapp_number ?? null);
    $email = $contact->email ?? ($candidate->user->email ?? null);
    $location = collect([$candidate->district, $candidate->region, $candidate->country])->filter()->implode(', ');
    $photoSrc = resumeImageSrc($candidate->photo ?? null);
    $siteName = $setting->site_name ?? 'Career Workforce';
    $experiences = ($candidate->experiences ?? collect())->take($compactPdf ? 3 : 10);
    $educations = ($candidate->educations ?? collect())->take($compactPdf ? 3 : 10);
    $skills = ($candidate->skills ?? collect())->take($compactPdf ? 12 : 40);
    $languages = ($candidate->languages ?? collect())->take($compactPdf ? 6 : 20);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $fullName }} — Resume</title>
    <style>
        @page { size: A4 portrait; margin: {{ $compactPdf ? '6mm' : '10mm' }}; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: {{ $compactPdf ? '9px' : '11px' }};
            line-height: {{ $compactPdf ? '1.3' : '1.45' }};
            color: #1e293b;
            background: {{ $compactPdf ? '#fff' : '#f8fafc' }};
        }
        .resume-page {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: {{ $compactPdf ? 'none' : '1px solid #e2e8f0' }};
            page-break-inside: avoid;
        }
        .resume-header {
            background: #0f172a;
            color: #fff;
            padding: {{ $compactPdf ? '10px 14px' : '22px 24px' }};
        }
        .resume-header-table { width: 100%; border-collapse: collapse; }
        .resume-header-table td { vertical-align: middle; padding: 0; border: none; }
        .resume-photo {
            width: {{ $compactPdf ? '56px' : '82px' }};
            height: {{ $compactPdf ? '56px' : '82px' }};
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,.25);
        }
        .resume-name {
            font-size: {{ $compactPdf ? '16px' : '22px' }};
            font-weight: bold;
            letter-spacing: -0.02em;
            margin-bottom: 2px;
        }
        .resume-headline {
            font-size: {{ $compactPdf ? '10px' : '12px' }};
            color: #cbd5e1;
            margin-bottom: {{ $compactPdf ? '4px' : '8px' }};
        }
        .resume-contact {
            font-size: {{ $compactPdf ? '8px' : '10px' }};
            color: #e2e8f0;
        }
        .resume-contact span { margin-right: 8px; }
        .resume-body { padding: {{ $compactPdf ? '10px 14px 12px' : '20px 24px 24px' }}; }
        .section { margin-bottom: {{ $compactPdf ? '8px' : '16px' }}; page-break-inside: avoid; }
        .section-title {
            font-size: {{ $compactPdf ? '9px' : '11px' }};
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #2563eb;
            border-bottom: 1.5px solid #2563eb;
            padding-bottom: 2px;
            margin-bottom: {{ $compactPdf ? '5px' : '10px' }};
        }
        .summary {
            font-size: {{ $compactPdf ? '9px' : '11px' }};
            color: #334155;
            line-height: {{ $compactPdf ? '1.35' : '1.6' }};
        }
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col > tbody > tr > td { vertical-align: top; padding: 0; border: none; }
        .col-main { width: 62%; padding-right: {{ $compactPdf ? '10px' : '16px' }} !important; }
        .col-side { width: 38%; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .data-table th,
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: {{ $compactPdf ? '2px 5px' : '5px 8px' }};
            text-align: left;
            font-size: {{ $compactPdf ? '8px' : '10px' }};
        }
        .data-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            width: 38%;
        }
        .exp-item { margin-bottom: {{ $compactPdf ? '5px' : '10px' }}; padding-left: 8px; border-left: 2px solid #2563eb; }
        .exp-title { font-weight: bold; font-size: {{ $compactPdf ? '9px' : '11px' }}; color: #0f172a; }
        .exp-meta { font-size: {{ $compactPdf ? '8px' : '10px' }}; color: #64748b; margin: 1px 0 2px; }
        .chip-row { line-height: 1.6; }
        .chip {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 1px 5px;
            margin: 1px 2px 1px 0;
            font-size: {{ $compactPdf ? '7.5px' : '9px' }};
        }
        .doc-row { width: 100%; border-collapse: collapse; }
        .doc-row td { width: 50%; vertical-align: top; padding: 2px; border: none; }
        .doc-img {
            max-width: {{ $compactPdf ? '110px' : '180px' }};
            max-height: {{ $compactPdf ? '70px' : '120px' }};
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .resume-footer {
            border-top: 1px solid #e2e8f0;
            padding: {{ $compactPdf ? '5px 14px' : '10px 24px' }};
            text-align: center;
            font-size: {{ $compactPdf ? '7px' : '9px' }};
            color: #94a3b8;
        }
        .brand-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: {{ $compactPdf ? '4px 14px' : '8px 24px' }};
            font-size: {{ $compactPdf ? '7px' : '9px' }};
            color: #64748b;
        }
        .brand-bar table { width: 100%; border-collapse: collapse; }
        .brand-bar td { border: none; padding: 0; vertical-align: middle; }
        .qr-cell { text-align: right; width: {{ $compactPdf ? '48px' : '70px' }}; }
        .qr-cell img, .qr-cell svg { width: {{ $compactPdf ? '40px' : '56px' }}; height: {{ $compactPdf ? '40px' : '56px' }}; }
        @media print {
            body { background: #fff; }
            .resume-page { border: none; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="resume-page">

    <div class="brand-bar">
        <table>
            <tr>
                <td>
                    @if(!empty($setting->favicon_image_url))
                        <img src="{{ resumeImageSrc($setting->favicon_image_url) }}" alt="" style="height:22px;vertical-align:middle;margin-right:6px;">
                    @endif
                    <strong style="color:#334155;">{{ $siteName }}</strong>
                </td>
                <td class="qr-cell">
                    @if(!empty($qrCodeSvg))
                        {!! $qrCodeSvg !!}
                    @elseif(!empty($qrCode))
                        <img src="data:image/png;base64,{{ $qrCode }}" alt="QR">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="resume-header">
        <table class="resume-header-table">
            <tr>
                @if($photoSrc)
                <td style="width:92px;padding-right:14px;">
                    <img src="{{ $photoSrc }}" alt="" class="resume-photo">
                </td>
                @endif
                <td>
                    <div class="resume-name">{{ $fullName }}</div>
                    @if($headline)
                        <div class="resume-headline">{{ $headline }}</div>
                    @endif
                    <div class="resume-contact">
                        @if($email)<span>{{ $email }}</span>@endif
                        @if($phone)<span>{{ $phone }}</span>@endif
                        @if($location)<span>{{ $location }}</span>@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="resume-body">

        @if(!empty(strip_tags($candidate->bio ?? '')))
        <div class="section">
            <div class="section-title">Professional Summary</div>
            <div class="summary">{{ $compactPdf ? \Illuminate\Support\Str::limit(strip_tags($candidate->bio), 420) : strip_tags($candidate->bio) }}</div>
        </div>
        @endif

        <table class="two-col">
            <tr>
                <td class="col-main">

                    @if($experiences->count())
                    <div class="section">
                        <div class="section-title">Work Experience</div>
                        @foreach($experiences as $exp)
                            <div class="exp-item">
                                <div class="exp-title">{{ $exp->designation ?: '—' }}</div>
                                <div class="exp-meta">
                                    {{ $exp->company }}
                                    @if($exp->department) · {{ $exp->department }} @endif
                                    ·
                                    {{ $exp->start ? date('M Y', strtotime($exp->start)) : '—' }}
                                    –
                                    {{ $exp->currently_working ? 'Present' : ($exp->end ? date('M Y', strtotime($exp->end)) : '—') }}
                                </div>
                                @if(!empty($exp->responsibilities))
                                    <div>{{ $compactPdf ? \Illuminate\Support\Str::limit(strip_tags($exp->responsibilities), 160) : strip_tags($exp->responsibilities) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @endif

                    @if($educations->count())
                    <div class="section">
                        <div class="section-title">Education</div>
                        <table class="data-table">
                            <tr>
                                <th>Level</th>
                                <th>Degree</th>
                                <th>Year</th>
                            </tr>
                            @foreach($educations as $edu)
                            <tr>
                                <td>{{ $edu->level ?: '—' }}</td>
                                <td>{{ $edu->degree ?: '—' }}</td>
                                <td>{{ $edu->year ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                    @endif

                </td>
                <td class="col-side">

                    <div class="section">
                        <div class="section-title">Personal Details</div>
                        <table class="data-table">
                            @if($candidate->birth_date)
                            <tr><th>Date of Birth</th><td>{{ date('d M Y', strtotime($candidate->birth_date)) }}</td></tr>
                            @endif
                            @if($candidate->gender)
                            <tr><th>Gender</th><td>{{ ucfirst($candidate->gender) }}</td></tr>
                            @endif
                            @if($candidate->marital_status)
                            <tr><th>Marital Status</th><td>{{ ucfirst($candidate->marital_status) }}</td></tr>
                            @endif
                            @if($candidate->nationality)
                            <tr><th>Nationality</th><td>{{ $candidate->nationality }}</td></tr>
                            @endif
                            @if($location)
                            <tr><th>Location</th><td>{{ $location }}</td></tr>
                            @endif
                        </table>
                    </div>

                    @if($candidate->passport_number || $candidate->cnic_number)
                    <div class="section">
                        <div class="section-title">Passport &amp; ID</div>
                        <table class="data-table">
                            @if($candidate->passport_number)
                            <tr><th>Passport No.</th><td>{{ strtoupper($candidate->passport_number) }}</td></tr>
                            @endif
                            @if($candidate->passport_issue_date)
                            <tr><th>Issue Date</th><td>{{ date('d M Y', strtotime($candidate->passport_issue_date)) }}</td></tr>
                            @endif
                            @if($candidate->passport_expiry_date)
                            <tr><th>Expiry Date</th><td>{{ date('d M Y', strtotime($candidate->passport_expiry_date)) }}</td></tr>
                            @endif
                            @if($candidate->place_of_issue)
                            <tr><th>Place of Issue</th><td>{{ $candidate->place_of_issue }}</td></tr>
                            @endif
                            @if($candidate->cnic_number)
                            <tr><th>National ID</th><td>{{ $candidate->cnic_number }}</td></tr>
                            @endif
                        </table>
                    </div>
                    @endif

                    @if($skills->count())
                    <div class="section">
                        <div class="section-title">Skills</div>
                        <div class="chip-row">
                            @foreach($skills as $skill)
                                <span class="chip">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($languages->count())
                    <div class="section">
                        <div class="section-title">Languages</div>
                        <div class="chip-row">
                            @foreach($languages as $lang)
                                <span class="chip">{{ $lang->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($jobRequirement) && ($jobRequirement->region || $jobRequirement->salary))
                    <div class="section">
                        <div class="section-title">Job Preferences</div>
                        <table class="data-table">
                            @if($jobRequirement->region)
                            <tr><th>Region</th><td>{{ $jobRequirement->region }}</td></tr>
                            @endif
                            @if($jobRequirement->salary)
                            <tr><th>Expected Salary</th><td>{{ $jobRequirement->currency ?? '' }} {{ number_format($jobRequirement->salary) }}</td></tr>
                            @endif
                        </table>
                    </div>
                    @endif

                </td>
            </tr>
        </table>

        @if(isset($attachments) && ($attachments?->passport_image || $attachments?->license_image))
        <div class="section">
            <div class="section-title">Attachments</div>
            <table class="doc-row">
                <tr>
                    <td>
                        @if($attachments?->license_image)
                            @php $licenseSrc = resumeImageSrc('storage/candidates/' . $attachments->license_image); @endphp
                            @if($licenseSrc)
                                <div style="font-size:9px;color:#64748b;margin-bottom:4px;">License</div>
                                <img src="{{ $licenseSrc }}" alt="License" class="doc-img">
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($attachments?->passport_image)
                            @php $passportSrc = resumeImageSrc('storage/candidates/' . $attachments->passport_image); @endphp
                            @if($passportSrc)
                                <div style="font-size:9px;color:#64748b;margin-bottom:4px;">Passport</div>
                                <img src="{{ $passportSrc }}" alt="Passport" class="doc-img">
                            @endif
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        @endif

    </div>

    <div class="resume-footer">
        Generated via {{ $siteName }} · {{ now()->format('d M Y') }}
    </div>
</div>
</body>
</html>
