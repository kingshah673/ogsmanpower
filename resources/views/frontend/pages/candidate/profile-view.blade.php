@extends('components.website.candidate.layout.app')

@section('title')
    {{ $firstName }} {{ $lastName }} – {{ __('profile') }}
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/candidate-profile-view.css') }}?v={{ @filemtime(public_path('css/candidate-profile-view.css')) ?: '1' }}">
@endsection

@section('main')
@php
    $fullName = trim($firstName . ' ' . $lastName) ?: auth()->user()->name;
    $headline = $candidate->title ?? (optional($candidate->profession)->name);
    $hasPassportSection = $candidate->passport_number
        || $candidate->cnic_number
        || $candidate->passport_expiry_date
        || ($attachments?->passport_image);
@endphp

<div class="dashboard-wrapper seeker-profile-page">
    <div class="container">
        <div class="dashboard-right">

            <div class="cw-settings-header">
                <div>
                    <h1>{{ __('View Profile') }}</h1>
                    <p>{{ __('Your complete professional profile at a glance.') }}</p>
                    @if(!empty($candidate->public_code))
                        <p class="mb-0 mt-1"><strong>Code:</strong> <code>{{ $candidate->public_code }}</code></p>
                    @endif
                </div>
                <div class="pv-actions no-print">
                    <a href="{{ route('candidate.setting') }}" class="pv-topbar-btn">
                        <i class="fas fa-cog"></i> {{ __('Edit Settings') }}
                    </a>
                    <a href="{{ route('candidate.view.cv') }}" class="pv-topbar-btn">
                        <i class="fas fa-file-pdf"></i> {{ __('Printable Resume') }}
                    </a>
                    <button type="button" onclick="window.print()" class="pv-topbar-btn pv-topbar-btn--primary">
                        <i class="fas fa-print"></i> {{ __('Print / Save PDF') }}
                    </button>
                </div>
            </div>

            {{-- Hero --}}
            <div class="profile-hero">
                <img src="{{ asset($candidate->photo) }}" alt="{{ $fullName }}" class="profile-hero__photo">
                <div>
                    <h2 class="profile-hero__name">{{ $fullName }}</h2>
                    @if($headline)
                        <p class="profile-hero__title">{{ $headline }}</p>
                    @endif
                    <div class="profile-badges">
                        @if($candidate->status == 'available')
                            <span class="profile-badge profile-badge--success"><i class="fas fa-check-circle"></i> Available</span>
                        @elseif($candidate->status == 'not_available')
                            <span class="profile-badge profile-badge--muted">Not Available</span>
                        @endif
                        @if($candidate->gender)
                            <span class="profile-badge">{{ ucfirst($candidate->gender) }}</span>
                        @endif
                        @if($candidate->marital_status)
                            <span class="profile-badge">{{ ucfirst($candidate->marital_status) }}</span>
                        @endif
                        @if($candidate->country)
                            <span class="profile-badge"><i class="fas fa-map-marker-alt"></i> {{ $candidate->country }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            @if($contact || $candidate->whatsapp_number)
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="far fa-envelope"></i> {{ __('Contact Information') }}</h3>
                    <div class="pv-field-grid">
                        @if($contact && $contact->phone)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('phone') }}</label>
                            <div class="pv-readonly-value">{{ $contact->phone }}</div>
                        </div>
                        @endif
                        @if($contact && $contact->secondary_phone)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Secondary Phone') }}</label>
                            <div class="pv-readonly-value">{{ $contact->secondary_phone }}</div>
                        </div>
                        @endif
                        @if($candidate->whatsapp_number)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">WhatsApp</label>
                            <div class="pv-readonly-value">{{ $candidate->whatsapp_number }}</div>
                        </div>
                        @endif
                        @if($contact && $contact->email)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('email') }}</label>
                            <div class="pv-readonly-value">{{ $contact->email }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Personal --}}
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="far fa-user"></i> {{ __('basic_information') }}</h3>
                    <div class="pv-field-grid">
                        @if($candidate->birth_date)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('birth_date') }}</label>
                            <div class="pv-readonly-value">{{ date('d M Y', strtotime($candidate->birth_date)) }}</div>
                        </div>
                        @endif
                        @if($candidate->gender)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('gender') }}</label>
                            <div class="pv-readonly-value">{{ ucfirst($candidate->gender) }}</div>
                        </div>
                        @endif
                        @if($candidate->marital_status)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('marital_status') }}</label>
                            <div class="pv-readonly-value">{{ ucfirst($candidate->marital_status) }}</div>
                        </div>
                        @endif
                        @if($candidate->country)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('country') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->country }}</div>
                        </div>
                        @endif
                        @if($candidate->region)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Region / State') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->region }}</div>
                        </div>
                        @endif
                        @if($candidate->district)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('City') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->district }}</div>
                        </div>
                        @endif
                        @if($candidate->experience?->name)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('experience') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->experience->name }}</div>
                        </div>
                        @endif
                        @if($candidate->education?->name)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('education') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->education->name }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Passport --}}
            @if($hasPassportSection)
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-passport"></i> {{ __('Passport & Identification') }}</h3>
                    <div class="pv-field-grid">
                        @if($candidate->passport_number)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('passport number') }}</label>
                            <div class="pv-readonly-value">{{ strtoupper($candidate->passport_number) }}</div>
                        </div>
                        @endif
                        @if($candidate->passport_issue_date)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Issue Date') }}</label>
                            <div class="pv-readonly-value">{{ date('d M Y', strtotime($candidate->passport_issue_date)) }}</div>
                        </div>
                        @endif
                        @if($candidate->passport_expiry_date)
                        @php
                            $expiry = \Carbon\Carbon::parse($candidate->passport_expiry_date);
                            $isExpired = $expiry->isPast();
                            $isExpiringSoon = ! $isExpired && $expiry->diffInMonths(now()) < 6;
                        @endphp
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Expiry Date') }}</label>
                            <div class="pv-readonly-value {{ $isExpired ? 'pv-readonly-value--danger' : ($isExpiringSoon ? 'pv-readonly-value--warning' : '') }}">
                                {{ date('d M Y', strtotime($candidate->passport_expiry_date)) }}
                                @if($isExpired) (Expired) @elseif($isExpiringSoon) (Expiring soon) @endif
                            </div>
                        </div>
                        @endif
                        @if($candidate->place_of_issue)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Place of Issue') }}</label>
                            <div class="pv-readonly-value">{{ $candidate->place_of_issue }}</div>
                        </div>
                        @endif
                        @if($candidate->cnic_number)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">National ID / CNIC</label>
                            <div class="pv-readonly-value">{{ $candidate->cnic_number }}</div>
                        </div>
                        @endif
                    </div>
                    @if($attachments?->passport_image)
                    <div class="pv-doc-preview">
                        <label class="pv-readonly-label">{{ __('Passport Image') }}</label>
                        <img src="{{ asset('storage/candidates/' . $attachments->passport_image) }}"
                             alt="Passport" class="pv-doc-img">
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Summary --}}
            @if($candidate->bio)
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="far fa-id-card"></i> {{ __('Summary') }}</h3>
                    <div class="pv-bio-block">{!! $candidate->bio !!}</div>
                </div>
            </div>
            @endif

            {{-- Skills --}}
            @if($candidate->skills && $candidate->skills->count())
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-tags"></i> {{ __('Skills') }}</h3>
                    <div class="pv-tags">
                        @foreach($candidate->skills as $skill)
                            <span class="pv-tag">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Languages --}}
            @if($candidate->languages && $candidate->languages->count())
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-language"></i> {{ __('Language') }}</h3>
                    <div class="pv-tags">
                        @foreach($candidate->languages as $lang)
                            <span class="pv-tag">{{ $lang->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Experience --}}
            @if($candidate->experiences && $candidate->experiences->count())
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-briefcase"></i> {{ __('experience') }}</h3>
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th>{{ __('company') }}</th>
                                    <th>{{ __('designation') }}</th>
                                    <th>{{ __('department') }}</th>
                                    <th>{{ __('Period') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidate->experiences as $exp)
                                <tr>
                                    <td><strong>{{ $exp->company }}</strong></td>
                                    <td>{{ $exp->designation }}</td>
                                    <td>{{ $exp->department ?: '—' }}</td>
                                    <td>
                                        {{ $exp->start ? date('M Y', strtotime($exp->start)) : '—' }}
                                        –
                                        {{ $exp->currently_working ? 'Present' : ($exp->end ? date('M Y', strtotime($exp->end)) : '—') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Education --}}
            @if($candidate->educations && $candidate->educations->count())
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-graduation-cap"></i> {{ __('education') }}</h3>
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Level') }}</th>
                                    <th>{{ __('Degree') }}</th>
                                    <th>{{ __('Year') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidate->educations as $edu)
                                <tr>
                                    <td>{{ $edu->level ?: '—' }}</td>
                                    <td>{{ $edu->degree ?: '—' }}</td>
                                    <td>{{ $edu->year ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Job requirements --}}
            @if($jobRequirement && ($jobRequirement->region || $jobRequirement->salary))
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-briefcase"></i> {{ __('Job Requirment') }}</h3>
                    <div class="pv-field-grid">
                        @if($jobRequirement->region)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Preferred Region') }}</label>
                            <div class="pv-readonly-value">{{ $jobRequirement->region }}</div>
                        </div>
                        @endif
                        @if($jobRequirement->salary)
                        <div class="pv-readonly-field">
                            <label class="pv-readonly-label">{{ __('Expected Salary') }}</label>
                            <div class="pv-readonly-value">{{ $jobRequirement->currency ?? '' }} {{ number_format($jobRequirement->salary) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Social --}}
            @if($socials && $socials->count())
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-share-alt"></i> {{ __('Social Setting') }}</h3>
                    <div class="pv-socials">
                        @foreach($socials as $social)
                            @if($social->url)
                            <a href="{{ $social->url }}" target="_blank" rel="noopener" class="pv-social-link">
                                <i class="fas fa-external-link-alt"></i> {{ ucfirst($social->social_media) }}
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- License attachment --}}
            @if($attachments?->license_image)
            <div class="glass-card">
                <div class="glass-card-body">
                    <h3><i class="fas fa-id-card"></i> {{ __('License') }}</h3>
                    <img src="{{ asset('storage/candidates/' . $attachments->license_image) }}"
                         alt="License" class="pv-doc-img">
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
