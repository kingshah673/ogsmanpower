{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('profile') }}
@endsection
@section('main')

    <div class="dashboard-wrapper seeker-settings-page">
        <div class="container">
            <div class="dashboard-right">
                <div class="cw-settings-header">
                    <div>
                        <h1>{{ __('Configure Profile') }}</h1>
                        <p>{{ __('Update and manage your complete professional profile.') }}</p>
                    </div>
                    <a href="{{ route('candidate.profile.view') }}" class="pv-topbar-btn" target="_blank" rel="noopener">
                        <i class="fas fa-eye"></i>
                        {{ __('View Public Profile') }}
                    </a>
                </div>

                <div class="ai-upload-card" id="cv-upload-sec">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <span class="cw-ai-badge">AI Co-Pilot</span>
                            <h4 class="mb-1">Smart Profile Auto-Builder</h4>
                            <p class="text-light mb-0">Upload your CV or passport to parse and auto-fill profile fields.</p>
                        </div>
                        <div id="aiLoader" class="d-none bg-light border rounded px-3 py-1 small text-muted">
                            <i class="fas fa-spinner fa-spin mr-1 text-primary"></i> Processing...
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            @php
                                $savedCvLabel = null;
                                if (!empty($candidate->cv)) {
                                    $latestCvResume = $resumes->sortByDesc('id')->first();
                                    $savedCvLabel = $latestCvResume?->name ?? basename($candidate->cv);
                                }
                            @endphp
                            <div class="upload-box-glass text-center" onclick="triggerUpload('cvUpload')">
                                <div class="upload-inner">
                                    <div class="upload-icon"><i class="fas fa-file-invoice"></i></div>
                                    <p id="cvText" class="mb-1 {{ $savedCvLabel ? 'upload-done' : '' }}">
                                        @if($savedCvLabel)
                                            &#10003; {{ $savedCvLabel }}
                                        @else
                                            Select Professional CV / Resume
                                        @endif
                                    </p>
                                    <small>PDF or image (JPG, PNG)</small>
                                </div>
                                <input type="file" id="cvUpload" accept=".pdf,.jpg,.jpeg,.png" hidden>
                            </div>
                            <button type="button" onclick="uploadCV()" class="btn btn-ai w-100 mt-2">
                                <i class="fas fa-magic mr-1"></i> Extract &amp; Autofill CV
                            </button>
                        </div>
                        <div class="col-md-6">
                            @php
                                $savedPassportLabel = (isset($attachments) && $attachments?->passport_image)
                                    ? __('Passport saved')
                                    : null;
                            @endphp
                            <div class="upload-box-glass text-center" onclick="triggerUpload('passportUpload')">
                                <div class="upload-inner">
                                    <div class="upload-icon"><i class="fas fa-passport"></i></div>
                                    <p id="passportText" class="mb-1 {{ $savedPassportLabel ? 'upload-done' : '' }}">
                                        @if($savedPassportLabel)
                                            &#10003; {{ $savedPassportLabel }}
                                        @else
                                            Select Passport Scan
                                        @endif
                                    </p>
                                    <small>Image or PDF</small>
                                </div>
                                <input type="file" id="passportUpload" accept="image/*,.pdf,application/pdf" hidden>
                            </div>
                            <button type="button" onclick="uploadPassport()" class="btn btn-ai w-100 mt-2">
                                <i class="fas fa-passport mr-1"></i> Extract &amp; Autofill Passport
                            </button>
                        </div>
                    </div>
                </div>

                <div id="extractionSummary" class="extraction-summary-card d-none">
                    <div class="es-header">
                        <h5 class="mb-0" id="summarySource">Extraction Complete</h5>
                        <button type="button" onclick="document.getElementById('extractionSummary').classList.add('d-none')" class="ai-close-btn border-0 bg-transparent">&times;</button>
                    </div>
                    <div class="es-body row">
                        <div class="es-col col-md-6">
                            <div class="es-col-title small font-weight-bold text-success mb-2"><i class="fa fa-check-circle mr-1"></i> Auto-filled</div>
                            <div id="filledList" class="es-tag-list"></div>
                        </div>
                        <div class="es-col col-md-6 mt-3 mt-md-0">
                            <div class="es-col-title small font-weight-bold text-warning mb-2"><i class="fa fa-exclamation-circle mr-1"></i> <span id="missingListTitle">Not on CV / optional</span></div>
                            <div id="notOnCvList" class="es-tag-list mb-2"></div>
                            <div id="missingList" class="es-tag-list"></div>
                        </div>
                    </div>
                </div>

                <div class="ats-card d-none" id="atsResult">
                    <h4>ATS Score</h4>
                    <div class="score-circle" id="scoreCircle">0%</div>
                    <div class="ats-details mt-3">
                        <div><strong>Matched Skills:</strong><ul id="matchedSkills"></ul></div>
                        <div><strong>Missing Skills:</strong><ul id="missingSkills"></ul></div>
                        <div><strong>Suggestions:</strong><ul id="suggestions"></ul></div>
                    </div>
                </div>

                <x-website.candidate.profile-completion-hints />

                <div class="cw-settings-layout">
                    <nav class="cw-chapter-nav" aria-label="Profile sections">
                        <p class="cw-chapter-nav__label">Profile Sections</p>
                        <a href="#basic-info-sec" class="cw-chapter-link"><i class="far fa-user"></i> {{ __('basic_information') }}</a>
                        <a href="#job-requirements-sec" class="cw-chapter-link"><i class="fas fa-briefcase"></i> {{ __('Job Requirment') }}</a>
                        <a href="#pro-details-sec" class="cw-chapter-link"><i class="far fa-id-card"></i> {{ __('Summary') }}</a>
                        <a href="#skills-sec" class="cw-chapter-link"><i class="fas fa-tags"></i> {{ __('Skills') }}</a>
                        <a href="#languages-sec" class="cw-chapter-link"><i class="fas fa-language"></i> {{ __('Language') }}</a>
                        <a href="#work-exp-sec" class="cw-chapter-link"><i class="fas fa-briefcase"></i> {{ __('experience') }}</a>
                        <a href="#social-sec" class="cw-chapter-link"><i class="fas fa-share-alt"></i> {{ __('Social Setting') }}</a>
                        <a href="#contact-sec" class="cw-chapter-link"><i class="far fa-envelope"></i> {{ __('Contact Setting') }}</a>
                        <a href="#attachment-sec" class="cw-chapter-link"><i class="fas fa-paperclip"></i> {{ __('Attachment') }}</a>
                        <a href="#privacy-sec" class="cw-chapter-link"><i class="fas fa-shield-alt"></i> {{ __('profile_privacy') }}</a>
                    </nav>

                    <div class="cw-settings-content cadidate-dashboard-tabs candidate">
                        <div>
                                {{-- Basic Setting  --}}
                                <div id="basic-info-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <!-- Header with title and button -->
                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                {{ __('basic_information') }}
                                            </h3>
                                            <button type="button" id="basicToggleForm" class="btn btn-icon tw-ml-4">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="tw-flex tw-items-center tw-gap-4" id="basicInfoPreview">


                                            <!-- Info Column -->


                                            <div class="profile_section d-flex">

                                                <div class="avatar">
                                                    <img src="{{ asset(auth()->user()->candidate->photo) }}" alt="image"
                                                        class="profile-image">
                                                </div>
                                                <div class="tw-flex tw-flex-col">
                                                    <span id="cwPreviewFullName" class="tw-text-lg tw-font-medium">{{ $firstName }}
                                                        {{ $lastName }}</span>
                                                    <span id="cwPreviewEmail"
                                                        class="tw-text-sm tw-text-gray-500">{{ $candidate->user->email }}</span>
                                                    <span id="cwPreviewWhatsapp"
                                                        class="tw-text-sm tw-text-gray-500">{{ $candidate->user->whatsapp }}</span>
                                                    @if ($candidate->country || $candidate->region || $candidate->district)
                                                        <span id="cwPreviewLocation" class="tw-text-sm tw-text-gray-500 mt-1">
                                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                                            {{ collect([$candidate->district, $candidate->region, $candidate->country])->filter()->implode(', ') }}
                                                        </span>
                                                    @else
                                                        <span id="cwPreviewLocation" class="tw-text-sm tw-text-gray-500 mt-1 d-none"></span>
                                                    @endif
                                                </div>

                                            </div>

                                        </div>


                                        <form id="basicForm" class="tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="basic">
                                            <div class="dashboard-account-setting-item tw-py-0">



                                                <div class="row col-lg-12">
                                                    <div class="col-lg-12">
                                                        <x-website.candidate.photo-section :candidate="$candidate" />
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="true" name="First Name"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <div class="fromGroup">
                                                            <div class="form-control-icon">
                                                                <x-forms.input type="text" name="first_name"
                                                                    value="{{ $firstName }}"
                                                                    placeholder="{{ __('first name') }}" class="" />
                                                            </div>
                                                            <span style="font-size: 8px"> Please note that the name must be
                                                                in accordance with the passport</span>

                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="true" name="Last Name"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <div class="fromGroup">
                                                            <div class="form-control-icon">
                                                                <x-forms.input type="text" name="last_name"
                                                                    value="{{ $lastName }}"
                                                                    placeholder="{{ __('last name') }}" class="" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="false" name="professional_title_tagline"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <div class="fromGroup">
                                                            <div class="form-control-icon">
                                                                <select name="title" class="cw-ms-select"
                                                                    data-cw-lookup="professions" data-cw-tags="1" data-cw-value="text"
                                                                    data-placeholder="Professional title or tagline">
                                                                    @if ($candidate->title)
                                                                        <option value="{{ $candidate->title }}" selected>{{ $candidate->title }}</option>
                                                                    @endif
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="true" name="experience_level"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <select name="experience" class="cw-ms-select"
                                                            data-cw-lookup="experiences" data-placeholder="Select experience level">
                                                            @if ($candidate->experience_id)
                                                                <option value="{{ $candidate->experience_id }}" selected>
                                                                    {{ $candidate->experience->name ?? '' }}
                                                                </option>
                                                            @endif
                                                        </select>
                                                        @error('experience')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="true" name="education_level"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <select name="education" class="cw-ms-select"
                                                            data-cw-lookup="educations" data-placeholder="Select education level">
                                                            @if ($candidate->education_id)
                                                                <option value="{{ $candidate->education_id }}" selected>
                                                                    {{ $candidate->education->name ?? '' }}
                                                                </option>
                                                            @endif
                                                        </select>
                                                        @error('education')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="col-lg-6 mb-3">
    <x-forms.label :required="true" name="date_of_birth"
        class="body-font-4 d-block text-gray-900 rt-mb-8" />

    <div class="fromGroup">
        <div class="d-flex align-items-center form-control-icon date datepicker">
            
            <input type="text"
    name="birth_date"
    value="{{ $candidate->birth_date ? date('d-m-Y', strtotime($candidate->birth_date)) : old('birth_date') }}"
    id="date"
    placeholder="dd-mm-yyyy"
    max="{{ now()->subYears(18)->format('d-m-Y') }}"
    class="form-control border-cutom @error('birth_date') is-invalid @enderror" />

            <label for="date" class="input-group-addon input-group-text-custom ogs-date-addon tw-cursor-pointer">
                <x-svg.calendar-icon />
            </label>
        </div>

        @error('birth_date')
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
</div>
                                                    <div class="dashboard-account-setting-item pb-0">
                                                        <h6>{{ __('location') }}</h6>
                                                        @if (config('templatecookie.map_show'))
                                                            <div class="row">

                                                                <div class="col-lg-12 mb-3">
                                                                    <x-website.map.map-warning />
                                                                    @php
                                                                        $map = $setting->default_map;
                                                                    @endphp
                                                                    <div id="google-map-div"
                                                                        class="{{ $map == 'google-map' ? '' : 'd-none' }}">
                                                                        <input id="searchInput" class="mapClass"
                                                                            type="text" placeholder="Enter a location">
                                                                        <div class="map mymap" id="google-map"></div>
                                                                    </div>
                                                                    <div class="{{ $map == 'leaflet' ? '' : 'd-none' }}">
                                                                        <input type="text" autocomplete="off"
                                                                            id="leaflet_search"
                                                                            placeholder="{{ __('enter_city_name') }}"
                                                                            class="full-width placeholder:tw-normal-case"
                                                                            value="{{ $candidate->exact_location ? $candidate->exact_location : $candidate->full_address }}" />
                                                                        <br>
                                                                        <div id="leaflet-map"></div>
                                                                    </div>
                                                                    @error('location')
                                                                        <span
                                                                            class="ml-3 text-md text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            @php
                                                                $session_location = session()->get('location');
                                                                $session_country =
                                                                    $session_location &&
                                                                    array_key_exists('country', $session_location)
                                                                        ? $session_location['country']
                                                                        : '-';
                                                                $session_exact_location =
                                                                    $session_location &&
                                                                    array_key_exists(
                                                                        'exact_location',
                                                                        $session_location,
                                                                    )
                                                                        ? $session_location['exact_location']
                                                                        : '-';

                                                                $candidate_country = $candidate->country;
                                                                $candidate_exact_location = $candidate->exact_location;
                                                            @endphp
                                                            <div class="glass-card-footer row mt-4 border-0">
                                                                <span>
                                                                    <img src="{{ asset('frontend/assets/images/loader.gif') }}"
                                                                        alt="loading" width="50px" height="50px"
                                                                        class="loader_position d-none">
                                                                </span>
                                                                <div class="location_secion">
                                                                    {{ __('country') }}: <span
                                                                        class="location_country">{{ $candidate_country ?: $session_country }}</span>
                                                                    <br>
                                                                    {{ __('full_address') }}: <span
                                                                        class="location_full_address">{{ $candidate_exact_location ?: $session_exact_location }}</span>
                                                                </div>
                                                            </div>
                                                        @else
                                                            @php
                                                                $basicLocationCountry = old('country', $candidate->basicLocationCountry());
                                                                $basicLocationState = old('state', $candidate->region);
                                                                $basicLocationCity = old('district', $candidate->district);
                                                            @endphp
                                                            {{-- Plain AJAX cascade replacing Livewire --}}
                                                            <div class="row mt-2" id="basic-location-wrap">
                                                                <div class="col-12 mb-3">
                                                                    <label class="body-font-4 d-block text-gray-900 rt-mb-8">Country</label>
                                                                    <select id="basic_country" name="country" class="form-control cw-static-select">
                                                                        <option value="">— Select Country —</option>
                                                                        @foreach($searchCountries as $sc)
                                                                            <option value="{{ $sc->name }}" {{ $basicLocationCountry == $sc->name ? 'selected' : '' }}>{{ $sc->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-12 mb-3">
                                                                    <label class="body-font-4 d-block text-gray-900 rt-mb-8">State / Region</label>
                                                                    <select id="basic_state" name="state" class="form-control cw-static-select" {{ $basicLocationCountry ? '' : 'disabled' }}>
                                                                        <option value="">— Select State —</option>
                                                                        @if($basicLocationState)
                                                                            <option value="{{ $basicLocationState }}" selected>{{ $basicLocationState }}</option>
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                                <div class="col-12 mb-3">
                                                                    <label class="body-font-4 d-block text-gray-900 rt-mb-8">City / District</label>
                                                                    <select id="basic_city" name="district" class="form-control cw-static-select" {{ $basicLocationState ? '' : 'disabled' }}>
                                                                        <option value="">— Select City —</option>
                                                                        @if($basicLocationCity)
                                                                            <option value="{{ $basicLocationCity }}" selected>{{ $basicLocationCity }}</option>
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="dashboard-account-setting-item pb-0">

                                                        <div class="row">
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true" name="gender"
                                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <select
                                                                    class="cw-static-select w-100-p @error('gender') is-invalid @enderror"
                                                                    name="gender">
                                                                    <option
                                                                        @if ($candidate->gender == 'male') selected @endif
                                                                        value="male">
                                                                        {{ __('male') }}
                                                                    </option>
                                                                    <option
                                                                        @if ($candidate->gender == 'female') selected @endif
                                                                        value="female">
                                                                        {{ __('female') }}
                                                                    </option>
                                                                    <option
                                                                        @if ($candidate->gender == 'transgender') selected @endif
                                                                        value="transgender">
                                                                        {{ __('Transgender') }}
                                                                    </option>
                                                                </select>
                                                                @error('gender')
                                                                    <span class="invalid-feedback"
                                                                        role="alert">{{ __($message) }}</span>
                                                                @enderror
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true" name="marital_status"
                                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <select name="marital_status"
                                                                    class="cw-static-select w-100-p">
                                                                    <option
                                                                        @if ($candidate->marital_status == 'married') selected @endif
                                                                        value="married">{{ __('married') }}</option>
                                                                    <option
                                                                        @if ($candidate->marital_status == 'single') selected @endif
                                                                        value="single">{{ __('single') }}</option>
                                                                </select>
                                                                @error('marital_status')
                                                                    <span class="invalid-feedback"
                                                                        role="alert">{{ __($message) }}</span>
                                                                @enderror
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true" name="profession"
                                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <select name="profession" class="cw-ms-select"
                                                                    data-cw-lookup="professions" data-cw-tags="1"
                                                                    data-placeholder="Select profession">
                                                                    @if ($candidate->profession_id)
                                                                        <option value="{{ $candidate->profession_id }}" selected>
                                                                            {{ $candidate->profession->name ?? '' }}
                                                                        </option>
                                                                    @endif
                                                                </select>
                                                                @error('profession')
                                                                    <span class="invalid-feedback"
                                                                        role="alert">{{ __($message) }}</span>
                                                                @enderror
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true" name="your_availability"
                                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <select id="available_status" name="status"
                                                                    class="cw-static-select form-control w-100-p">
                                                                    <option value="">{{ __('select_one') }}</option>
                                                                    <option
                                                                        {{ old('status', $candidate->status) == 'available' ? 'selected' : '' }}
                                                                        value="available">{{ __('available') }}</option>
                                                                    <option
                                                                        {{ old('status', $candidate->status) == 'not_available' ? 'selected' : '' }}
                                                                        value="not_available">{{ __('not_available') }}
                                                                    </option>
                                                                    <option
                                                                        {{ old('status', $candidate->status) == 'available_in' ? 'selected' : '' }}
                                                                        value="available_in">{{ __('available_in') }}
                                                                    </option>
                                                                </select>
                                                                @error('status')
                                                                    <span
                                                                        class="error invalid-feedback d-block">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                            <div class="col-lg-6 d-none" id="available_in_status1">
                                                                <div>
                                                                    <h4 class="f-size-14 ft-wt-5 rt-mb-20 lh-1">
                                                                        {{ __('available_in') }}</h4>
                                                                    <div
                                                                        class="d-flex align-items-center form-control-icon date datepicker">
                                                                        <input type="text" id="available_id_date1"
                                                                            name="available_in"
                                                                            value="{{ old('available_in', date('d-m-Y', strtotime($candidate->available_in))) }}"
                                                                            placeholder="dd/mm/yyyy"
                                                                            class="form-control border-cutom @error('available_in') is-invalid @enderror">
                                                                        <span
                                                                            class="input-group-addon input-group-text-custom">
                                                                            <x-svg.calendar-icon />
                                                                        </span>
                                                                    </div>
                                                                    @error('available_in')
                                                                        <span
                                                                            class="error invalid-feedback d-block">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
    <x-forms.label :required="true" name="Passport Number"
        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />

    <div class="fromGroup">
        <div class="form-control-icon">

            <x-forms.input 
                type="text"
                name="passport_number"
                value="{{ $candidate->passport_number }}"
                placeholder="{{ __('passport number') }}"
                pattern="[A-Za-z0-9]{6,15}"
                minlength="6"
                maxlength="15"
                title="Enter valid passport number (6-15 letters and numbers only)"
                class="" />

        </div>
    </div>
</div>
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true"
                                                                    name="Passport Issue Date"
                                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <div class="fromGroup">
                                                                    <div
                                                                        class="d-flex align-items-center form-control-icon date datepicker">
                                                                        <input type="text" name="passport_issue_date"
                                                                            value="{{ $candidate->passport_issue_date ? date('d-m-Y', strtotime($candidate->passport_issue_date)) : old('birth_date') }}"
                                                                            id="passportIssueDate"
                                                                            placeholder="dd/mm/yyyy"
                                                                            class="form-control border-cutom @error('passport_issue_date') is-invalid @enderror" />
                                                                        <label for="passportIssueDate"
                                                                            class="input-group-addon input-group-text-custom ogs-date-addon tw-cursor-pointer">
                                                                            <x-svg.calendar-icon />
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-6 mb-3">
    <x-forms.label :required="true"
        name="Passport Expiry Date"
        class="body-font-4 d-block text-gray-900 rt-mb-8" />

    <div class="fromGroup">
        <div class="d-flex align-items-center form-control-icon date datepicker">

            <input type="text"
                name="passport_expiry_date"
                value="{{ $candidate->passport_expiry_date ? date('d-m-Y', strtotime($candidate->passport_expiry_date)) : old('passport_expiry_date') }}"
                id="passportExpiryDate"
                placeholder="dd-mm-yyyy"
                class="form-control border-cutom @error('passport_expiry_date') is-invalid @enderror" />

            <label for="passportExpiryDate" class="input-group-addon input-group-text-custom ogs-date-addon tw-cursor-pointer">
                <x-svg.calendar-icon />
            </label>
        </div>

        <small id="passportExpiryError" class="text-danger">
            Passport expiry date must be at least 6 months valid.
        </small>
    </div>
</div>
                                                            <div class="col-lg-6 mb-3">
                                                                <x-forms.label :required="true" name="Place Of Issue"
                                                                    class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                                <div class="fromGroup">
                                                                    <div class="form-control-icon">
                                                                        <x-forms.input type="text"
                                                                            name="place_of_issue"
                                                                            value="{{ $candidate->place_of_issue }}"
                                                                            placeholder="{{ __('Place Of Issue') }}"
                                                                            class="" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
    <x-forms.label :required="true" name="National ID Number"
        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />

    <div class="fromGroup">
        <div class="form-control-icon">

            <x-forms.input 
                type="text"
                name="cnic_number"
                value="{{ $candidate->cnic_number }}"
                placeholder="{{ __('National ID / CNIC Number') }}"
                pattern="[A-Za-z0-9\-]{5,25}"
                minlength="5"
                maxlength="25"
                title="Enter valid national ID number"
                class="" />

        </div>

        <small class="text-muted">
            Supports worldwide national ID formats
        </small>
    </div>
</div>
                                                            @include('frontend.partials.dynamic-fields-section', ['section' => 'basic-info', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                            <div class="col-lg-12 ">
                                                                <button type="submit" class="btn btn-primary">
                                                                    {{ __('save_changes') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                {{-- Job Requirements --}}

                                <div id="job-requirements-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                {{ __('Job Requirment') }}</h3>
                                            <button type="button" id="jobToggleForm" class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>

                                        </div>
                                        <div id="jobPreview" class="cw-preview-tags">
                                            @php
                                                $jr = optional($jobRequirement);
                                                $previewJobLabels = cw_profession_labels($jr->jobs ?? null);
                                                $previewIndustryLabels = cw_industry_labels($jr->industries ?? null);
                                            @endphp
                                            <div class="mb-2">
                                                <span class="text-muted small d-block">Job titles</span>
                                                @forelse ($previewJobLabels as $label)
                                                    <span class="cw-tag">{{ $label }}</span>
                                                @empty
                                                    <span class="text-muted small">None selected</span>
                                                @endforelse
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted small d-block">Industries</span>
                                                @forelse ($previewIndustryLabels as $label)
                                                    <span class="cw-tag">{{ $label }}</span>
                                                @empty
                                                    <span class="text-muted small">None selected</span>
                                                @endforelse
                                            </div>
                                            <div class="small text-gray-800">
                                                <strong>Region:</strong> {{ $jr->region ?: '—' }}
                                                &nbsp;·&nbsp;
                                                <strong>Salary:</strong>
                                                {{ $jr->currency ? $jr->currency.' ' : '' }}{{ $jr->salary ?? '—' }}
                                                &nbsp;·&nbsp;
                                                <strong>Location:</strong>
                                                @if($jr->search_country_id && $jr->searchcountry)
                                                    {{ $jr->searchcountry->name }}
                                                    @if($jr->state) / {{ $jr->state->name }} @endif
                                                    @if($jr->city) / {{ $jr->city->name }} @endif
                                                @else
                                                    Anywhere
                                                @endif
                                            </div>
                                        </div>
                                        <form id="jobForm" class="tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="jobRequirements">
                                            <input type="hidden" name="jobs_payload" id="jobs_payload" value="">
                                            <input type="hidden" name="industries_payload" id="industries_payload" value="">
                                            <!-- Job Title -->
                                            <div class="col-lg-12 mb-3">
                                                <x-forms.label :required="true" name="Job Title"
                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                <select name="jobs[]" class="cw-ms-select" multiple
                                                    data-cw-lookup="professions" data-cw-tags="1"
                                                    data-placeholder="Search job titles…">
                                                    @foreach (cw_json_array(optional($jobRequirement)->jobs) as $jobValue)
                                                        @if(is_numeric($jobValue))
                                                            @php $jobProfession = \App\Models\Profession::find((int) $jobValue); @endphp
                                                            <option value="{{ (int) $jobValue }}" selected>{{ $jobProfession->name ?? $jobValue }}</option>
                                                        @else
                                                            <option value="{{ $jobValue }}" selected>{{ $jobValue }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <span style="font-size: 8px"> Please add at least five Job Titles.</span>
                                            </div>

                                            <!-- Industries -->
                                            <div class="col-lg-12 mb-3">
                                                <x-forms.label :required="true" name="Industries"
                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                <select name="industries[]" class="cw-ms-select" multiple
                                                    data-cw-lookup="industries" data-cw-tags="1"
                                                    data-placeholder="Search industries…">
                                                    @foreach (cw_json_array(optional($jobRequirement)->industries) as $industryValue)
                                                        @if(is_numeric($industryValue))
                                                            @php $industryModel = \App\Models\IndustryType::find((int) $industryValue); @endphp
                                                            <option value="{{ (int) $industryValue }}" selected>{{ $industryModel->name ?? $industryValue }}</option>
                                                        @else
                                                            <option value="{{ $industryValue }}" selected>{{ $industryValue }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <span style="font-size: 8px"> Please add at least five Industries.</span>

                                            </div>
                                            <!-- Region -->
                                            <div class="col-lg-12 mb-3">
                                                <x-forms.label :required="true" name="Region"
                                                    class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                <select id="region" name="region" class="cw-static-select w-100-p" required>
                                                    <option value="" disabled {{ empty(optional($jobRequirement)->region) ? 'selected' : '' }}>Select Region</option>
                                                    @foreach (['Anywhere', 'Gulf', 'Asia', 'Europe'] as $region)
                                                        <option value="{{ $region }}"
                                                            {{ optional($jobRequirement)->region == $region ? 'selected' : '' }}>
                                                            {{ $region }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Salary -->
                                            <div class="form-group">
                                                <label for="salary"
                                                    class="block text-sm font-medium text-gray-700 mb-2">Salary</label>
                                                <div class="flex gap-4 items-center">
                                                    <select name="currency" id="currency"
                                                        class="block w-28 border-gray-300 rounded-md shadow-sm">
                                                        @php
                                                            $currencies = [
                                                                'USD' => 'USD ($)',
                                                                'EUR' => 'EUR (â‚¬)',
                                                                'GBP' => 'GBP (Â£)',
                                                                'PKR' => 'PKR (â‚¨)',
                                                                'JPY' => 'JPY (Â¥)',
                                                                'AED' => 'AED (Ø¯.Ø¥)',
                                                                'SAR' => 'SAR (ï·¼)',
                                                                'QAR' => 'QAR (Ø±.Ù‚)',
                                                                'KWD' => 'KWD (Ø¯.Ùƒ)',
                                                                'OMR' => 'OMR (ï·¼)',
                                                                'BHD' => 'BHD (Ø¨.Ø¯)',
                                                            ];
                                                        @endphp
                                                        @foreach ($currencies as $code => $label)
                                                            <option value="{{ $code }}"
                                                                {{ $jobRequirement?->currency == $code ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <input type="number" name="salary" id="salary"
                                                        class="block w-full border-gray-300 rounded-md shadow-sm"
                                                        placeholder="Enter salary amount" step="0.01" min="0"
                                                        value="{{ optional($jobRequirement)->salary ?? '' }}">
                                                </div>
                                            </div>

                                            <!-- Location -->
                                            <div id="location-dvi" class="dashboard-account-setting-item pb-0 location-dvi">
                                                <h6>{{ __('Location') }}</h6>
                                                <div>
                                                    <!-- Country Dropdown -->
                                                    <div>
                                                        <select id="country" name="country" class="select21 cw-static-select max-w-100">

                                                            <option value="anywhere"
                                                                {{ empty(optional($jobRequirement)->search_country_id) ? 'selected' : '' }}>
                                                                Anywhere
                                                            </option>
                                                            @foreach ($searchCountries as $country)
                                                                <option value="{{ $country->id }}"
                                                                    {{ optional($jobRequirement)->search_country_id == $country->id ? 'selected' : '' }}>
                                                                    {{ $country->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <!-- State Dropdown (Initially Empty) -->
                                                    <div>
                                                        <select id="state" name="state" class="select21 cw-static-select max-w-100">
                                                            <option value="">Select State</option>
                                                            @if(optional($jobRequirement)->state_id && optional($jobRequirement)->state)
                                                                <option value="{{ $jobRequirement->state_id }}" selected>{{ $jobRequirement->state->name }}</option>
                                                            @endif
                                                        </select>
                                                    </div>

                                                    <!-- City Dropdown (Initially Empty) -->
                                                    <div>
                                                        <select id="city" name="district" class="select21 cw-static-select max-w-100">
                                                            <option value="">Select City</option>
                                                            @if(optional($jobRequirement)->city_id && optional($jobRequirement)->city)
                                                                <option value="{{ $jobRequirement->city_id }}" selected>{{ $jobRequirement->city->name }}</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            @include('frontend.partials.dynamic-fields-section', ['section' => 'job-requirements', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                            <!-- Submit Button -->
                                            <div class="col-lg-12 mt-5">
                                                <button type="submit" class="btn btn-primary">
                                                    {{ __('save_changes') }}
                                                </button>
                                            </div>
                                        </form>

                                    </div>

                                </div>

                                {{-- Summary --}}
                                <div id="pro-details-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">

                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <div class="tw-flex tw-items-center tw-gap-4">
                                                <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                    {{ __('Summary') }}
                                                </h3>

                                            </div>
                                            <button type="button" id="summaryToggleForm" class="btn btn-icon tw-ml-4">

                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="tw-flex tw-items-center tw-gap-4" id="summaryPreview">
                                            <div class="">
                                                {!! $candidate->bio !!}
                                            </div>
                                        </div>
                                        <form id="summaryForm" class=" tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="summary">
                                            <div class="dashboard-account-setting-item tw-py-0">

                                                <div class="row">

                                                    <div class="row col-lg-8">
                                                        <div class="col-lg-12 mb-3">
                                                            <x-forms.label :required="false" name="Summary"
                                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                            <textarea name="bio" id="candidate_bio" class="form-control" rows="8" placeholder="{{ __('bio') }}">{{ strip_tags($candidate->bio ?? '') }}</textarea>
                                                            @error('bio')
                                                                <span class="text-danger">{{ __($message) }}</span>
                                                            @enderror
                                                            <button type="button" onclick="generateBioWithAI()" class="btn btn-sm btn-ai mt-2">
                                                                ✨ Generate Bio with AI
                                                            </button>
                                                        </div>
                                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'pro-details', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                        <div class="col-lg-12 mt-4">
                                                            <button type="submit" class="btn btn-primary">
                                                                {{ __('save_changes') }}
                                                            </button>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                {{-- Skills --}}
                                <div id="skills-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">

                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                {{ __('Skills') }}</h3>

                                            <button type="button" id="skillToggleForm" class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div id="skillPreview" class="cw-preview-tags">
                                            @forelse ($candidate->skills ?? [] as $skill)
                                                <span class="cw-tag">{{ $skill->name }}</span>
                                            @empty
                                                <span class="text-muted small">No skills added yet.</span>
                                            @endforelse
                                        </div>
                                        <form id="skillForm" class=" tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="skill">
                                            <div class="dashboard-account-setting-item tw-py-0">
                                                <div class="row">
                                                    <div class="row col-lg-8">
                                                        <div class="col-lg-12 mb-3">
                                                            <x-forms.label :required="true" name="skills_you_have"
                                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                            <select name="skills[]" class="cw-ms-select" multiple
                                                                data-cw-lookup="skills" data-cw-tags="1"
                                                                data-placeholder="Type to search skills…">
                                                                @foreach ($candidate->skills ?? [] as $skill)
                                                                    <option value="{{ $skill->id }}" selected>{{ $skill->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'skills', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                        <div class="col-lg-12 mt-4">
                                                            <button type="submit" class="btn btn-primary">
                                                                {{ __('save_changes') }}
                                                            </button>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                {{-- Language --}}
                                <div id="languages-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">

                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                {{ __('Language') }}</h3>
                                            <button type="button" id="languageToggleForm" class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div id="languagePreview" class="cw-preview-tags">
                                            @forelse ($candidate->languages ?? [] as $lang)
                                                <span class="cw-tag"><i class="fas fa-comment mr-1"></i>{{ $lang->name }}</span>
                                            @empty
                                                <span class="text-muted small">No languages added yet.</span>
                                            @endforelse
                                        </div>
                                        <form id="languageForm" class=" tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="language">
                                            <div class="dashboard-account-setting-item tw-py-0">
                                                <div class="row">
                                                    <div class="row col-lg-8">
                                                        <div class="col-lg-12 mb-3">
                                                            <x-forms.label :required="true" name="languages_you_know"
                                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                                            <select name="languages[]" class="cw-ms-select" multiple
                                                                data-cw-lookup="languages"
                                                                data-placeholder="Search languages…">
                                                                @foreach ($candidate->languages ?? [] as $lang)
                                                                    <option value="{{ $lang->id }}" selected>{{ $lang->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'languages', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                        <div class="col-lg-12 mt-4">
                                                            <button type="submit" class="btn btn-primary">
                                                                {{ __('save_changes') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                {{-- Experience & Education Setting  --}}
                                <div id="work-exp-sec" class="glass-card mt-4 form-section-anchor">

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <x-website.candidate.tab.candidate-experience-setting-tab :experiences="$candidate->experiences" />

                                    <x-website.candidate.tab.candidate-education-setting-tab :educations="$candidate->educations" />
                                </div>
                                {{-- Social Setting  --}}
                                <div id="social-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">{{ __('Social Setting') }}
                                            </h3>
                                            <button type="button" id="socialToggleForm" class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="tw-flex tw-items-center tw-gap-4" id="socialPreview">
                                            <div class="">
                                                @foreach ($socials as $social)
                                                    <p>
                                                        {{ $social->social_media }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div id="socialForm" class="dashboard-account-setting-item tw-hidden">
                                            <form action="{{ route('candidate.settingUpdate', [], false) }}" method="POST">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="type" value="social">
                                                <div class="row">
                                                    @forelse($socials as $social)
                                                        <div class="col-12 custom-select-padding">
                                                            <div class="d-flex tw-items-center">
                                                                <div class="d-flex mborder">
                                                                    <div class="position-relative">
                                                                        <select
                                                                            class="w-100-p border-0 new-select form-control"
                                                                            name="social_media[]">
                                                                            <option value="" class="d-none"
                                                                                disabled>
                                                                                {{ __('select_one') }}</option>
                                                                            <option
                                                                                {{ $social->social_media == 'facebook' ? 'selected' : '' }}
                                                                                value="facebook">{{ __('facebook') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'twitter' ? 'selected' : '' }}
                                                                                value="twitter">{{ __('twitter') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'instagram' ? 'selected' : '' }}
                                                                                value="instagram">
                                                                                {{ __('instagram') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'youtube' ? 'selected' : '' }}
                                                                                value="youtube">{{ __('youtube') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'linkedin' ? 'selected' : '' }}
                                                                                value="linkedin">{{ __('linkedin') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'pinterest' ? 'selected' : '' }}
                                                                                value="pinterest">
                                                                                {{ __('pinterest') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'reddit' ? 'selected' : '' }}
                                                                                value="reddit">{{ __('reddit') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'github' ? 'selected' : '' }}
                                                                                value="github">{{ __('github') }}
                                                                            </option>
                                                                            <option
                                                                                {{ $social->social_media == 'other' ? 'selected' : '' }}
                                                                                value="other">{{ __('other') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="w-100">
                                                                        <input class="border-0" type="url"
                                                                            name="url[]" id=""
                                                                            placeholder="{{ __('profile_link_url') }}..."
                                                                            value="{{ $social->url }}">
                                                                    </div>
                                                                </div>
                                                                <div class="tw-ms-2">
                                                                    <button
                                                                        class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center"
                                                                        type="button" id="remove_item">
                                                                        <svg width="24" height="24"
                                                                            viewBox="0 0 24 24" fill="none"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path
                                                                                d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z"
                                                                                stroke="#18191C" stroke-width="1.5"
                                                                                stroke-miterlimit="10" />
                                                                            <path d="M15 9L9 15" stroke="#18191C"
                                                                                stroke-width="1.5" stroke-linecap="round"
                                                                                stroke-linejoin="round" />
                                                                            <path d="M15 15L9 9" stroke="#18191C"
                                                                                stroke-width="1.5" stroke-linecap="round"
                                                                                stroke-linejoin="round" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="col-12 custom-select-padding">
                                                            <div class="d-flex tw-items-center">
                                                                <div class="d-flex mborder">
                                                                    <div class="position-relative">
                                                                        <select
                                                                            class="w-100-p border-0 new-select form-control"
                                                                            name="social_media[]">
                                                                            <option value="" class="d-none" disabled
                                                                                selected>
                                                                                {{ __('select_one') }}</option>
                                                                            <option value="facebook">
                                                                                {{ __('facebook') }}
                                                                            </option>
                                                                            <option value="twitter">
                                                                                {{ __('twitter') }}
                                                                            </option>
                                                                            <option value="instagram">
                                                                                {{ __('instagram') }}
                                                                            </option>
                                                                            <option value="youtube">
                                                                                {{ __('youtube') }}
                                                                            </option>
                                                                            <option value="linkedin">
                                                                                {{ __('linkedin') }}
                                                                            </option>
                                                                            <option value="pinterest">
                                                                                {{ __('pinterest') }}
                                                                            </option>
                                                                            <option value="reddit">
                                                                                {{ __('reddit') }}
                                                                            </option>
                                                                            <option value="github">
                                                                                {{ __('github') }}
                                                                            </option>
                                                                            <option value="other">
                                                                                {{ __('other') }}
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="w-100">
                                                                        <input class="border-0" type="url"
                                                                            name="url[]" id=""
                                                                            placeholder="{{ __('profile_link_url') }}...">
                                                                    </div>
                                                                </div>
                                                                <div class="tw-ms-2">
                                                                    <button
                                                                        class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center"
                                                                        type="button" id="remove_item">
                                                                        <svg width="24" height="24"
                                                                            viewBox="0 0 24 24" fill="none"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path
                                                                                d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z"
                                                                                stroke="#18191C" stroke-width="1.5"
                                                                                stroke-miterlimit="10" />
                                                                            <path d="M15 9L9 15" stroke="#18191C"
                                                                                stroke-width="1.5" stroke-linecap="round"
                                                                                stroke-linejoin="round" />
                                                                            <path d="M15 15L9 9" stroke="#18191C"
                                                                                stroke-width="1.5" stroke-linecap="round"
                                                                                stroke-linejoin="round" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforelse
                                                    <div id="multiple_feature_part">
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn tw-bg-[#F1F2F4] w-100 mt-4 add-new-social"
                                                            onclick="add_features_field()" type="button">
                                                            <svg width="20" height="20" viewBox="0 0 20 20"
                                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M10 17.5C14.1421 17.5 17.5 14.1421 17.5 10C17.5 5.85786 14.1421 2.5 10 2.5C5.85786 2.5 2.5 5.85786 2.5 10C2.5 14.1421 5.85786 17.5 10 17.5Z"
                                                                    stroke="#18191C" stroke-width="1.5"
                                                                    stroke-miterlimit="10" />
                                                                <path d="M6.875 10H13.125" stroke="#18191C"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                                <path d="M10 6.875V13.125" stroke="#18191C"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round" />
                                                            </svg>
                                                            <span>{{ __('add_new_social_link') }}</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                @include('frontend.partials.dynamic-fields-section', ['section' => 'social', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                <button type="submit" class="btn btn-primary mt-4">
                                                    {{ __('save_changes') }}
                                                </button>
                                        </div>

                                        </form>
                                    </div>
                                </div>
                                {{-- Account Setting  --}}
                                <div id="contact-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="tw-flex lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                {{ __('Contact Setting') }}
                                            </h3>


                                            <button type="button" id="accountToggleForm" class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>

                                        @if($contact)

                                        <div id="accountPreview">
                                            <h6>{{ __('your_contact_information') }}</h6>
                                            <p><strong>{{ __('phone') }}:</strong> {{ $contact->phone }}</p>
                                            <p><strong>{{ __('secondary_phone') }}:</strong> {{ $contact->secondary_phone }}</p>
                                            <p><strong>{{ __('whatsapp_number') }}:</strong> {{ $candidate->whatsapp_number }}</p>
                                            <p><strong>{{ __('email_address') }}:</strong> {{ $contact->email }}</p>
                                        </div>

                                        @endif


                                        <form id="accountForm" class="tw-hidden"
                                            action="{{ route('candidate.settingUpdate', [], false) }}" method="POST">
                                            @csrf
                                            @method('put')
                                            <input type="hidden" name="type" value="contact">

                                            <div class="dashboard-account-setting-item">
                                                <h6>{{ __('your_contact_information') }}</h6>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="false" name="phone"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <x-forms.input type="text" name="phone"
                                                            value="{{ old('phone', $contact->phone ?? '') }}" id="phone"
                                                            placeholder="{{ __('phone') }}" class="phonecode"
                                                            data-initial-country="{{ $phoneCountryIso ?? default_phone_country_iso() }}" />
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="false" name="secondary_phone"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <x-forms.input type="text" name="secondary_phone"
                                                            value="{{ old('secondary_phone', $contact->secondary_phone ?? '') }}" id="phone2"
                                                            placeholder="{{ __('phone') }}" class="phonecode"
                                                            data-initial-country="{{ $phoneCountryIso ?? default_phone_country_iso() }}" />
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="false" name="whatsapp_number"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <x-forms.input type="text" name="whatsapp_number"
                                                            value="{{ old('whatsapp_number', $candidate->whatsapp_number ?? '') }}"
                                                            id="whatsapp_number"
                                                            placeholder="{{ __('whatsapp_number') }}"
                                                            class="phonecode"
                                                            data-initial-country="{{ $phoneCountryIso ?? default_phone_country_iso() }}" />
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <x-forms.label :required="false" name="email"
                                                            class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                        <div class="fromGroup has-icon2">
                                                            <div class="form-control-icon">
                                                                <x-forms.input type="email" name="email"
                                                                    value="{{ $contact->email }}" id=""
                                                                    placeholder="{{ __('email_address') }}"
                                                                    class="" />
                                                                <div class="icon-badge-2">
                                                                    <x-svg.envelope-icon />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @include('frontend.partials.dynamic-fields-section', ['section' => 'contact', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                                <button type="submit" class="btn btn-primary mt-4">
                                                    {{ __('save_changes') }}
                                                </button>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                                {{-- Attachment Setting  --}}
                                <div id="attachment-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                            <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">{{ __('Attachment') }}</h3>


                                            <button type="button" id="attachmentToggleForm"
                                                class="btn btn-icon tw-ml-4 ">
                                                <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        @php
                                            $passportImg = isset($attachments) && $attachments->passport_image
                                                ? asset('storage/candidates/' . $attachments->passport_image)
                                                : null;
                                            $licenseImg = isset($attachments) && $attachments->license_image
                                                ? asset('storage/candidates/' . $attachments->license_image)
                                                : null;
                                            $placeholderImg = asset('images/candidates/img1.jpg');
                                        @endphp
                                        <div id="attachmentForm">
                                            <div class="row">

                                                <!-- Passport Image Section -->
                                                <div class="form-group col-md-6">
                                                    <label>Passport Image</label>
                                                    <span class="attachment-saved-badge {{ $passportImg ? '' : 'd-none' }}"
                                                        id="passportSavedBadge">&#10003; Saved</span>
                                                    <div class="custom-file">
                                                        <input type="file" id="passportImageInput"
                                                            class="custom-file-input attachment-instant-input"
                                                            data-field="passport_image" data-preview="passportImagePreview"
                                                            data-badge="passportSavedBadge" data-delete="deletePassportBtn"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,image/*">
                                                        <label class="custom-file-label" for="passportImageInput">Choose
                                                            File</label>
                                                    </div>
                                                    <center class="pt-4">
                                                        <img style="height: 200px; max-width: 100%; border: 1px solid #ddd; border-radius: 10px; object-fit: contain;"
                                                            id="passportImagePreview"
                                                            src="{{ $passportImg ?? $placeholderImg }}"
                                                            alt="passport-image">
                                                        <div class="mt-2">
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger attachment-delete-btn {{ $passportImg ? '' : 'd-none' }}"
                                                                id="deletePassportBtn" data-field="passport_image"
                                                                data-preview="passportImagePreview"
                                                                data-badge="passportSavedBadge"
                                                                data-placeholder="{{ $placeholderImg }}">
                                                                &times; Delete Passport Image
                                                            </button>
                                                        </div>
                                                    </center>
                                                </div>

                                                <!-- License Image Section -->
                                                <div class="form-group col-md-6">
                                                    <label>License Image</label>
                                                    <span class="attachment-saved-badge {{ $licenseImg ? '' : 'd-none' }}"
                                                        id="licenseSavedBadge">&#10003; Saved</span>
                                                    <div class="custom-file">
                                                        <input type="file" id="licenseImageInput"
                                                            class="custom-file-input attachment-instant-input"
                                                            data-field="license_image" data-preview="licenseImagePreview"
                                                            data-badge="licenseSavedBadge" data-delete="deleteLicenseBtn"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff,image/*">
                                                        <label class="custom-file-label" for="licenseImageInput">Choose
                                                            File</label>
                                                    </div>
                                                    <center class="pt-4">
                                                        <img style="height: 200px; max-width: 100%; border: 1px solid #ddd; border-radius: 10px; object-fit: contain;"
                                                            id="licenseImagePreview"
                                                            src="{{ $licenseImg ?? $placeholderImg }}"
                                                            alt="license-image">
                                                        <div class="mt-2">
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger attachment-delete-btn {{ $licenseImg ? '' : 'd-none' }}"
                                                                id="deleteLicenseBtn" data-field="license_image"
                                                                data-preview="licenseImagePreview"
                                                                data-badge="licenseSavedBadge"
                                                                data-placeholder="{{ $placeholderImg }}">
                                                                &times; Delete License Image
                                                            </button>
                                                        </div>
                                                    </center>
                                                </div>

                                                <div class="col-lg-12 mt-2">
                                                    <small class="text-muted">Images are saved automatically the moment you
                                                        choose them — just like your profile photo.</small>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Job Alert --}}
                                <div class="glass-card tw-mb-4">
                                    <div class="glass-card-body">

                                        <div class="dashboard-account-setting-item setting-border">
                                            <div class="tw-flex  lg:tw-mt-0 tw-items-center tw-justify-between">
                                                <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">{{ __('job_alert') }}
                                                </h3>

                                                <button type="button" id="jobalertToggleForm"
                                                    class="btn btn-icon tw-ml-4 ">
                                                    <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div id="jobalertPreview">
                                                <h6>{{ __('status') }}: <span class="{{ $candidate->received_job_alert ? 'text-green' : 'text-red' }}">{{ $candidate->received_job_alert ? __('enabled') : __('disabled') }}</span></h6>
                                                <ul>
                                                    @if ($candidate->jobRoleAlerts && count($candidate->jobRoleAlerts))
                                                        @foreach ($candidate->jobRoleAlerts as $jobRoleAlert)
                                                            <li>
                                                                {{ $jobRoleAlert->job_role_id ? \App\Models\JobRole::find($jobRoleAlert->job_role_id)->name : __('no_job_role_selected') }}
                                                            </li>
                                                        @endforeach
                                                    @endif
                                                </ul>
                                            </div>
                                            <div id="jobalertForm" class="row tw-hidden">
                                                <form id="alert" action="{{ route('candidate.settingUpdate', [], false) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('put')
                                                    <input type="hidden" name="type" value="alert">
                                                    <input type="hidden" name="alert_type" value="status">

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6>{{ __('Alert') }}</h6>
                                                        <div class="input-group-text bg-transparent border-0"
                                                            id="basic-addon1">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" value="0"
                                                                    name="received_job_alert">
                                                                <input name="received_job_alert" class="form-check-input"
                                                                    type="checkbox" id="flexSwitchCheckDefault"
                                                                    value="1"
                                                                    {{ $candidate->received_job_alert ? 'checked' : '' }}>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <form id="jobalertRolesForm" action="{{ route('candidate.settingUpdate', [], false) }}" method="POST">
                                                    @csrf
                                                    @method('put')
                                                    <input type="hidden" name="type" value="alert">
                                                    <input type="hidden" name="alert_type" value="role">
                                                    <div class="col-lg-12">
                                                        <x-forms.label :required="false" name="choose_job_role"
                                                            class="f-size-14 text-gray-700" />
                                                        <div>
                                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                                <select class="cw-ms-select" multiple name="job_roles[]"
                                                                    data-cw-lookup="job_roles" data-cw-tags="1"
                                                                    data-placeholder="Search job roles…">
                                                                    @foreach ($candidate->jobRoleAlerts ?? [] as $alert)
                                                                        @if ($alert->jobRole)
                                                                            <option value="{{ $alert->job_role_id }}" selected>{{ $alert->jobRole->name }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                                <div>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        {{ __('save_changes') }}
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <br>
                                                            <p>
                                                                [{{ __('note_you_will_be_notified_for_this_role_only') }}]
                                                            </p>
                                                            <div class="form-control-icon">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Profile Privacy --}}
                                <div id="privacy-sec" class="glass-card tw-mb-4 form-section-anchor">
                                    <div class="glass-card-body">
                                        <div class="dashboard-account-setting-item setting-border">
                                            <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                                <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">

                                                    {{ __('profile_privacy') }}</h3>

                                                <button type="button" id="profilePolicyToggleForm"
                                                    class="btn btn-icon tw-ml-4 ">
                                                    <svg class="ogs-pencil" width="20" height="20" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12 20h9" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div id="profilePolicyPreview">
                                                <p>{{ __('profile_privacy') }}: <span class="{{ $candidate->visibility ? 'text-green' : 'text-red' }}">{{ $candidate->visibility ? __('public') : __('private') }}</span></p>
                                            </div>
                                            <div id="profilePolicyForm" class="tw-hidden">
                                                <form id="visibility" action="{{ route('candidate.settingUpdate', [], false) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('put')
                                                    <input type="hidden" name="type" value="visibility">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            {{-- <label class="text-gray-900 rt-mb-15 fw-medium">{{ __('profile_privacy') }}</label> --}}

                                                            <div class="input-group mb-3">
                                                                <div class="input-group-text bg-transparent border border-gray-50 extra-design"
                                                                    id="basic-addon1">
                                                                    <div class="form-check form-switch">
                                                                        <input name="profile_visibility"
                                                                            class="form-check-input" type="checkbox"
                                                                            id="flexSwitchCheckDefault"
                                                                            {{ $candidate->visibility ? 'checked' : '' }}>
                                                                        <span
                                                                            class="form-check-label f-size-14">{{ __('yes') }}</span>
                                                                    </div>
                                                                </div>
                                                                <input disabled type="text" class="form-control"
                                                                    placeholder="Your profile is {{ $candidate->visibility ? 'public' : 'private' }} now"
                                                                    id="msalary">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- Resume add modal --}}
    <div class="modal fade" id="resumeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog tw-max-w-[536px]">
            <div class="modal-content">
                <form action="{{ route('candidate.resume.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <h5 class="tw-text-lg tw-text-[#18191C] tw-font-semibold tw-mb-[18px]" id="cvModalLabel">
                            {{ __('add_cv_resume') }}</h5>
                        <div class="from-group py-2">
                            <x-forms.label name="cv_resume_name" :required="true"
                                class="tw-mb-2 tw-text-sm tw-text-[#18191C]" />
                            <input type="text" name="resume_name" id="">
                            @error('is_remote')
                                <span class="error invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group tw-mb-6">
                            <x-forms.label name="upload_cv_resume" class="tw-mb-2 tw-text-sm tw-text-[#18191C]" />
                            <div class="cv-image-upload-wrap">
                                <input name="resume_file" class="resume-file-upload-input" type="file"
                                    onchange="resumeManageReadURL(this, 'add');" accept="application/pdf"
                                    id="resume_add_input" />
                                <div class="drag-text">
                                    <x-svg.upload-icon />
                                    <h3>{{ __('browse_file') }}</h3>
                                    <p>{{ __('available_format') }} - PDF<br>
                                        {{ __('maximum_file_size') }} - 5 MB</p>
                                </div>
                            </div>
                            <div class="resume-file-upload-content none ">
                                <div class="wrap">
                                    <x-svg.file-icon2 />
                                    <h3 class="resume_selected_file_name">file</h3>
                                    <p>
                                        <span><span class="resume_selected_file_size">2.3</span> MB</span> <br>
                                        <span class="resume_selected_file_type">.pdf</span>
                                    </p>
                                    <div class="image-title-wrap">
                                        <button type="button" class="cv-remove-image">
                                            <x-svg.trash-icon />
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="tw-flex tw-justify-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50" data-bs-dismiss="modal"
                                aria-label="Close">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('add_cv_resume') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                        <button type="button"
                            class="tw-rounded-full tw-flex tw-items-center tw-justify-center tw-p-3 tw-absolute -tw-top-[25px] -tw-right-[25px] tw-bg-white tw-border-2 tw-border-[#E7F0FA]"
                            data-bs-dismiss="modal" aria-label="Close">
                            <x-svg.modal-cross-icon />
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Resume edit modal --}}
    <div class="modal fade" id="resumeEditModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog tw-max-w-[536px]">
            <div class="modal-content">
                <form action="{{ route('candidate.resume.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="resume_id" id="resume_id_input">
                    <div class="modal-body">
                        <h5 class="tw-text-lg tw-text-[#18191C] tw-font-semibold tw-mb-[18px]" id="cvModalLabel">
                            {{ __('update_cv_resume') }}</h5>
                        <div class="from-group py-2">
                            <x-forms.label name="cv_resume_name" :required="true"
                                class="tw-mb-2 tw-text-sm tw-text-[#18191C]" />
                            <input type="text" name="resume_name" id="resume_name_input">
                            @error('is_remote')
                                <span class="error invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group tw-mb-6">
                            <x-forms.label name="upload_cv_resume" class="tw-mb-2 tw-text-sm tw-text-[#18191C]" />
                            <div class="cv-image-upload-wrap">
                                <input name="resume_file" class="resume-file-upload-input" type="file"
                                    onchange="resumeManageReadURL(this, 'edit');" accept="application/pdf"
                                    id="resume_edit_input" />
                                <div class="drag-text">
                                    <x-svg.upload-icon />
                                    <h3>{{ __('change_file') }}</h3>
                                    <p>{{ __('current_resume_size') }}: <span id="resume_file_size"></span></p>
                                </div>
                            </div>
                            <div class="resume-file-upload-content none ">
                                <div class="wrap">
                                    <x-svg.file-icon2 />
                                    <h3 class="resume_selected_file_name">file</h3>
                                    <p>
                                        <span><span class="resume_selected_file_size">2.3</span> MB</span> <br>
                                        <span class="resume_selected_file_type">.pdf</span>
                                    </p>
                                    <div class="image-title-wrap">
                                        <button type="button" class="cv-remove-image">
                                            <x-svg.trash-icon />
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="tw-flex tw-justify-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50" data-bs-dismiss="modal"
                                aria-label="Close">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('add_cv_resume') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                        <button type="button"
                            class="tw-rounded-full tw-flex tw-items-center tw-justify-center tw-p-3 tw-absolute -tw-top-[25px] -tw-right-[25px] tw-bg-white tw-border-2 tw-border-[#E7F0FA]"
                            data-bs-dismiss="modal" aria-label="Close">
                            <x-svg.modal-cross-icon />
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Add Education Modal --}}
    <div class="modal fade" id="addEducationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('candidate.educations.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <h5 class="modal-title rt-mb-18 f-size-18" id="cvModalLabel">{{ __('add_education') }}</h5>
                        <div class="from-group rt-mb-18">
                            <x-forms.label name="education_level" class="rt-mb-8" />
                            <input type="text" name="level" required class="@error('level') is-invalid @enderror"
                                placeholder="{{ __('enter') }} {{ __('education_level') }}">
                            @error('level')
                                <span class="error invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="degree" class="rt-mb-8" />
                                <input type="text" name="degree" required
                                    class="@error('degree') is-invalid @enderror"
                                    placeholder="{{ __('enter') }} {{ __('degree') }}">
                                @error('degree')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <x-forms.label name="year" class="rt-mb-8" />
                                <input type="text" name="year" value="{{ old('year') }}" placeholder="year"
                                    class="year_picker form-control border-cutom @error('year') is-invalid @enderror">
                            </div>
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-12">
                                <x-forms.label name="notes" class="rt-mb-8" :required="false" />
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="{{ __('enter') }} {{ __('notes') }}" name="notes" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="d-flex tw-flex-wrap tw-gap-4 justify-content-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50"
                                onclick="closeAddEducationModal()">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('add_education') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
                <button type="button" class="btn-close" onclick="closeAddEducationModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.75 5.25L5.25 18.75" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18.75 18.75L5.25 5.25" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>


    {{-- Edit Eduction Modal --}}
    <div class="modal fade" id="editEducationModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('candidate.educations.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <h5 class="modal-title rt-mb-18 f-size-18" id="cvModalLabel">{{ __('edit_education') }}</h5>
                        <input type="hidden" name="education_id" id="education-modal-id">
                        <div class="from-group rt-mb-18">
                            <x-forms.label name="education_level" class="rt-mb-8" />
                            <input id="education-modal-level" type="text" name="level" required
                                placeholder="{{ __('enter') }} {{ __('education_level') }}">
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="degree" class="rt-mb-8" />
                                <input id="education-modal-degree" type="text" name="degree" required
                                    placeholder="{{ __('enter') }} {{ __('degree') }}">
                                @error('degree')
                                    <span class="error invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <x-forms.label name="year" class="rt-mb-8" />
                                <input id="education-modal-year" type="text" name="year"
                                    value="{{ old('year') }}" placeholder="d/m/y"
                                    class="year_picker form-control border-cutom @error('year') is-invalid @enderror"
                                    required>
                            </div>
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-12">
                                <x-forms.label name="notes" class="rt-mb-8" :required="false" />
                                <textarea id="education-notes" class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="{{ __('enter') }} {{ __('notes') }}" name="notes" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="d-flex tw-flex-wrap tw-gap-4 justify-content-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50"
                                onclick="closeEditEducationModal()">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('update_education') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
                <button type="button" class="btn-close" onclick="closeEditEducationModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.75 5.25L5.25 18.75" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18.75 18.75L5.25 5.25" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Add Experience Modal --}}
    <div class="modal fade" id="addExperienceModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('candidate.experiences.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <h5 class="modal-title rt-mb-18 f-size-18" id="cvModalLabel">{{ __('add_experience') }}</h5>
                        <div class="from-group rt-mb-18">
                            <x-forms.label name="company" class="rt-mb-8" />
                            <input type="text" name="company"
                                class="@error('company') is-invalid @enderror"
                                placeholder="{{ __('enter') }} {{ __('company') }}">

                            @error('company')
                                <span class="error invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="department" class="rt-mb-8" />
                                <input type="text" name="department" 
                                    placeholder="{{ __('enter') }} {{ __('department') }}">
                            </div>
                            <div class="col-lg-6">
                                <x-forms.label name="designation" class="rt-mb-8" />
                                <input type="text" name="designation" required
                                    placeholder="{{ __('enter') }} {{ __('designation') }}">
                            </div>
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="start_date" class="rt-mb-8" />
                                <input type="text" name="start" value="{{ old('start') }}"
                                    placeholder="yyyy-mm-dd"
                                    class="date_picker form-control border-cutom @error('start') is-invalid @enderror"
                                    required>
                            </div>
                            <div class="col-lg-6 experience_end_date">
                                <x-forms.label name="end_date" class="rt-mb-8" />
                                <input type="text" name="end" value="{{ old('end') }}"
                                    placeholder="yyyy-mm-dd"
                                    class="date_picker form-control border-cutom @error('end') is-invalid @enderror">
                            </div>
                        </div>
                        <div class="from-group d-flex gap-2 align-items-center rt-mb-24 custom-checkbox">
                            <input type="checkbox" name="currently_working" id="experience-modal-checkbox_create"
                                value="1">
                            <x-forms.label name="i_am_currently_working" for="experience-modal-checkbox_create"
                                :required="false" class="!tw-mb-0 tw-cursor-pointer" />
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-12">
                                <x-forms.label name="responsibilities" class="rt-mb-8" :required="false" />
                                <textarea class="form-control @error('responsibilities') is-invalid @enderror"
                                    placeholder="{{ __('enter') }} {{ __('responsibilities') }}" name="responsibilities" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="d-flex tw-flex-wrap tw-gap-4 justify-content-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50"
                                onclick="closeAddExperienceModal()">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('add_experience') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
                <button type="button" class="btn-close" onclick="closeAddExperienceModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.75 5.25L5.25 18.75" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18.75 18.75L5.25 5.25" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>


    {{-- Edit Experience Modal --}}
    <div class="modal fade" id="editExperienceModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('candidate.experiences.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <h5 class="modal-title rt-mb-18 f-size-18" id="cvModalLabel">{{ __('edit_experience') }}</h5>
                        <input type="hidden" name="experience_id" id="experience-modal-id">
                        <div class="from-group rt-mb-18">
                            <x-forms.label name="company" class="rt-mb-8" />
                            <input id="experience-modal-company" type="text" name="company" required
                                placeholder="{{ __('enter') }} {{ __('company') }}">
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="department" class="rt-mb-8" />
                                <input id="experience-modal-department" type="text" name="department" required
                                    placeholder="{{ __('enter') }} {{ __('department') }}">
                            </div>
                            <div class="col-lg-6">
                                <x-forms.label name="designation" class="rt-mb-8" />
                                <input id="experience-modal-designation" type="text" name="designation" required
                                    placeholder="{{ __('enter') }} {{ __('designation') }}">
                            </div>
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-6">
                                <x-forms.label name="start_date" class="rt-mb-8" />
                                <input id="experience-modal-start" type="text" name="start"
                                    value="{{ old('start') }}" placeholder="yyyy-mm-dd"
                                    class="date_picker form-control border-cutom @error('start') is-invalid @enderror"
                                    required>
                            </div>
                            <div class="col-lg-6 experience_end_date">
                                <x-forms.label name="end_date" class="rt-mb-8" :required="false" />
                                <input id="experience-modal-end" type="text" name="end"
                                    value="{{ old('end') }}" placeholder="yyyy-mm-dd"
                                    class="date_picker form-control border-cutom @error('end') is-invalid @enderror">
                            </div>
                        </div>
                        <div class="from-group d-flex gap-2 align-items-center rt-mb-24">
                            <input type="checkbox" name="currently_working" id="experience-modal-checkbox_edit"
                                value="1">
                            <x-forms.label name="i_am_currently_working" for="experience-modal-checkbox_edit"
                                :required="false" class="!tw-mb-0 !tw-cursor-pointer" />
                        </div>
                        <div class="row rt-mb-18">
                            <div class="col-lg-12">
                                <x-forms.label name="responsibilities" class="rt-mb-8" :required="false" />
                                <textarea id="experience-responsibilities" class="form-control @error('responsibilities') is-invalid @enderror"
                                    placeholder="{{ __('enter') }} {{ __('responsibilities') }}" name="responsibilities" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="d-flex tw-flex-wrap tw-gap-4 justify-content-between">
                            <button type="button" class="bg-priamry-50 btn btn-primary-50"
                                onclick="closeEditExperienceModal()">{{ __('cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="button-content-wrapper ">
                                    <span class="button-icon align-icon-right"><i class="ph-arrow-right"></i></span>
                                    <span class="button-text">
                                        {{ __('update_experience') }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
                <button type="button" class="btn-close" onclick="closeEditExperienceModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.75 5.25L5.25 18.75" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18.75 18.75L5.25 5.25" stroke="var(--primary-500)" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

@endsection

@section('frontend_links')
    <meta name="default-phone-country" content="{{ $phoneCountryIso ?? default_phone_country_iso() }}">
    <link rel="stylesheet" href="{{ asset('css/candidate-settings-classic.css') }}?v={{ @filemtime(public_path('css/candidate-settings-classic.css')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    @if (config('templatecookie.map_show'))
    <!-- >=>Leaflet Map<=< -->
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    @endif
@endsection

@section('frontend_scripts')
<script>window.cwDefaultPhoneCountry = @json($phoneCountryIso ?? default_phone_country_iso());</script>
<script>
(function () {
    var sections = document.querySelectorAll('.seeker-settings-page .form-section-anchor');
    var links = document.querySelectorAll('.seeker-settings-page .cw-chapter-link');
    if (!sections.length || !links.length) return;

    function setActiveChapter() {
        var current = '';
        var offset = 120;
        sections.forEach(function (section) {
            if (window.scrollY >= section.offsetTop - offset) {
                current = section.id;
            }
        });
        links.forEach(function (link) {
            link.classList.toggle('is-active', link.getAttribute('href') === '#' + current);
        });
    }

    window.addEventListener('scroll', setActiveChapter, { passive: true });
    setActiveChapter();
})();
</script>
<script>


/*---------passport expiry ------*/
$('#passportExpiryDate').datepicker({
    format: 'dd-mm-yyyy',
    startDate: '+0m', // disables all dates before 6 months
    autoclose: true
});

$('#passportExpiryDate').on('changeDate change', function () {

    let selectedDate = $(this).val();

    if (!selectedDate) return;

    let parts = selectedDate.split('-');

    let selected = new Date(parts[2], parts[1] - 1, parts[0]);

    let sixMonthsLater = new Date();
    sixMonthsLater.setMonth(sixMonthsLater.getMonth() + 6);

    // remove time for accurate comparison
    selected.setHours(0,0,0,0);
    sixMonthsLater.setHours(0,0,0,0);

    if (selected < sixMonthsLater) {

        $('#passportExpiryError')
            .removeClass('d-none')
            .text('Passport expiry date must be at least 6 months valid.');

        $(this).val('');
        $(this).addClass('is-invalid');

    } else {

        $('#passportExpiryError').addClass('d-none');

        $(this).removeClass('is-invalid');
    }
});
/* ===== GLOBAL AI DATA ===== */
let aiData = {
    first_names: ["Ali", "Ahmed", "Usman"],
    titles: ["Driver", "Security Guard", "Web Developer"],
    skills: ["Driving", "Security", "HTML", "CSS", "Communication"],
    jobs: ["Driver", "Warehouse Worker", "Helper"],
    summary: "Experienced professional with strong skills and international work exposure."
};

/* ===== AUTO-FILL HELPERS ===== */
var CV_OPTIONAL_FIELDS = [
    'Passport Number', 'Passport Issue', 'Passport Expiry', 'Place of Issue',
    'CNIC', 'Passport Details', 'Marital Status', 'Gender', 'Date of Birth',
    'Profile Photo', 'Salary Expectation', 'Languages', 'Website'
];

function fillInput(name, value) {
    if (!value) return false;
    let el = document.querySelector('[name="' + name + '"]');
    if (!el) return false;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
}

function fillPhoneField(id, value) {
    if (!value) return false;
    var el = document.getElementById(id);
    if (!el) return false;
    if (window.OgsIntlPhone && typeof window.OgsIntlPhone.init === 'function' && !el._ogsIti) {
        window.OgsIntlPhone.init(el);
    }
    if (el._ogsIti && typeof el._ogsIti.setNumber === 'function') {
        try { el._ogsIti.setNumber(value); return true; } catch (e) {}
    }
    el.value = value;
    return true;
}

function fillSelect(name, value) {
    if (!value) return false;
    let sel = $('select[name="' + name + '"]');
    if (!sel.length) return false;
    let matched = null;
    sel.find('option').each(function() {
        if ($(this).text().toLowerCase().includes(String(value).toLowerCase()) ||
            String($(this).val()).toLowerCase() === String(value).toLowerCase()) {
            matched = $(this).val();
            return false;
        }
    });
    if (matched !== null) {
        sel.val(matched).trigger('change');
    } else {
        var opt = new Option(value, value, true, true);
        sel.append(opt).trigger('change');
    }
    if (sel.hasClass('select2-hidden-accessible')) {
        sel.trigger('change.select2');
    }
    return true;
}

function fillMultiSelect(name, values) {
    if (!values || !Array.isArray(values) || !values.length) return false;
    let sel = $('select[name="' + name + '"]');
    if (!sel.length) return false;
    values.forEach(function(v) {
        if (!v) return;
        var exists = false;
        sel.find('option').each(function() {
            if ($(this).val() == v || $(this).text().toLowerCase() === String(v).toLowerCase()) {
                $(this).prop('selected', true);
                exists = true;
                return false;
            }
        });
        if (!exists) {
            sel.append(new Option(v, v, true, true));
        }
    });
    sel.trigger('change');
    if (sel.hasClass('select2-hidden-accessible')) {
        sel.trigger('change.select2');
    }
    return true;
}

function fillLookupMultiSelect(selectName, values, lookupType) {
    if (!values || !Array.isArray(values) || !values.length) return Promise.resolve(false);
    var sel = $('select[name="' + selectName + '"]');
    if (!sel.length) return Promise.resolve(false);
    var lookupUrl = (window.cwSettingsLookupUrl || '/candidate/settings/lookup') + '/' + lookupType;
    var selectedIds = [];

    return Promise.all(values.map(function(name) {
        if (!name) return Promise.resolve();
        return fetch(lookupUrl + '?q=' + encodeURIComponent(name), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var results = data.results || [];
            var match = results.find(function(item) {
                return String(item.text).toLowerCase() === String(name).toLowerCase();
            }) || results[0];
            var id = match ? String(match.id) : String(name);
            var text = match ? match.text : name;
            if (!sel.find('option[value="' + id.replace(/"/g, '\\"') + '"]').length) {
                sel.append(new Option(text, id, true, true));
            } else {
                sel.find('option[value="' + id.replace(/"/g, '\\"') + '"]').prop('selected', true);
            }
            selectedIds.push(id);
        })
        .catch(function() {
            var id = String(name);
            if (!sel.find('option[value="' + id.replace(/"/g, '\\"') + '"]').length) {
                sel.append(new Option(name, id, true, true));
            }
            selectedIds.push(id);
        });
    })).then(function() {
        if (selectedIds.length) {
            sel.val(selectedIds);
        }
        sel.trigger('change');
        if (sel.hasClass('select2-hidden-accessible')) {
            sel.trigger('change.select2');
        }
        return true;
    });
}

function cwLocationNamesMatch(a, b) {
    if (!a || !b) return false;
    var left = String(a).trim().toLowerCase();
    var right = String(b).trim().toLowerCase();
    return left === right || left.indexOf(right) !== -1 || right.indexOf(left) !== -1;
}

function cwFillBasicLocation(country, state, city) {
    if (!country) return;

    var $country = $('#basic_country');
    if ($country.length) {
        var matched = false;
        $country.find('option').each(function() {
            if (cwLocationNamesMatch($(this).text(), country) ||
                cwLocationNamesMatch($(this).val(), country)) {
                $country.val($(this).val());
                matched = true;
                return false;
            }
        });
        if (!matched) {
            $country.append(new Option(country, country, true, true));
            $country.val(country);
        }
        if ($country.hasClass('select2-hidden-accessible')) {
            $country.trigger('change.select2');
        } else {
            $country.trigger('change');
        }

        if (typeof window.cwLoadBasicLocation === 'function') {
            window.cwLoadBasicLocation(country, state || null, city || null);
        }
        return;
    }

    var parts = [city, state, country].filter(Boolean);
    var fullAddr = parts.join(', ');
    $('.location_country').text(country);
    $('.location_full_address').text(fullAddr);
    var mapInput = document.getElementById('leaflet_search') || document.getElementById('searchInput');
    if (mapInput) mapInput.value = fullAddr;
}

function renderTagList(containerId, items, className) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = (items || []).map(function (t) {
        return '<span class="cw-tag ' + (className || '') + '">' + t + '</span>';
    }).join('') || '<span class="text-muted small">—</span>';
}

function cwRefreshProfilePreviews(profile) {
    if (!profile) return;

    var nameEl = document.getElementById('cwPreviewFullName');
    if (nameEl && profile.full_name) nameEl.textContent = profile.full_name;

    var emailEl = document.getElementById('cwPreviewEmail');
    if (emailEl && profile.email) emailEl.textContent = profile.email;

    var waEl = document.getElementById('cwPreviewWhatsapp');
    if (waEl && profile.whatsapp) waEl.textContent = profile.whatsapp;

    var locEl = document.getElementById('cwPreviewLocation');
    if (locEl && profile.location) {
        locEl.classList.remove('d-none');
        locEl.innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> ' + profile.location;
    }

    var summaryEl = document.getElementById('summaryPreview');
    if (summaryEl && profile.bio) {
        summaryEl.innerHTML = '<div>' + profile.bio.replace(/\n/g, '<br>') + '</div>';
    }

    renderTagList('skillPreview', profile.skills || []);
    renderTagList('languagePreview', profile.languages || []);

    var accountPrev = document.getElementById('accountPreview');
    if (accountPrev) {
        accountPrev.innerHTML =
            '<h6>{{ __('your_contact_information') }}</h6>' +
            '<p><strong>{{ __('phone') }}:</strong> ' + (profile.phone || '—') + '</p>' +
            '<p><strong>{{ __('whatsapp_number') }}:</strong> ' + (profile.whatsapp || '—') + '</p>' +
            '<p><strong>{{ __('email_address') }}:</strong> ' + (profile.email || '—') + '</p>';
    }
}

function cwRefreshWorkTables(profile) {
    if (!profile) return;

    function renderExpRows(experiences) {
        return (experiences || []).map(function (e) {
            var end = e.currently_working ? '{{ __('currently_working') }}' : (e.end || '—');
            return '<tr><td>' + e.company + '</td><td>' + (e.department || '') + '</td><td>' +
                e.designation + '</td><td class="text-nowrap">' + (e.start || '') + ' – ' + end + '</td>' +
                '<td class="text-center text-nowrap">—</td></tr>';
        }).join('');
    }

    function renderEduRows(educations) {
        return (educations || []).map(function (e) {
            return '<tr><td>' + (e.level || '') + '</td><td>' + (e.degree || '') +
                '</td><td>' + (e.year || '') + '</td><td class="text-center text-nowrap">—</td></tr>';
        }).join('');
    }

    var expBody = document.getElementById('cwExperienceRows');
    if (expBody && profile.experiences && profile.experiences.length) {
        var addExpRow = expBody.querySelector('#addExperience');
        var addExpHtml = addExpRow ? addExpRow.closest('tr').outerHTML : '';
        expBody.innerHTML = renderExpRows(profile.experiences) + addExpHtml;
    }

    var eduBody = document.getElementById('cwEducationRows');
    if (eduBody && profile.educations && profile.educations.length) {
        var addEduRow = eduBody.querySelector('#addEducation');
        var addEduHtml = addEduRow ? addEduRow.closest('tr').outerHTML : '';
        eduBody.innerHTML = renderEduRows(profile.educations) + addEduHtml;
    }
}

function cwRefreshSocialPreview(links) {
    var el = document.getElementById('socialPreview');
    if (!el || !links || !links.length) return;
    el.innerHTML = '<div>' + links.map(function (l) {
        var label = (l.platform || l.social_media || 'link');
        return '<p><a href="' + l.url + '" target="_blank" rel="noopener">' + label + '</a></p>';
    }).join('') + '</div>';
}

function fillSocialLinks(links) {
    if (!links || !links.length) return false;
    var form = document.getElementById('socialForm');
    if (!form) return false;

    function fillRow(selectEl, platform, url) {
        if (!selectEl) return;
        selectEl.value = platform;
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        var row = selectEl.closest('.custom-select-padding');
        var urlInput = row ? row.querySelector('input[name="url[]"]') : null;
        if (urlInput) urlInput.value = url;
    }

    var selects = form.querySelectorAll('select[name="social_media[]"]');
    links.forEach(function (link, idx) {
        var platform = (link.platform || link.social_media || '').toLowerCase();
        var url = link.url || '';
        if (!platform || !url) return;
        if (idx < selects.length) {
            fillRow(selects[idx], platform, url);
        } else if (typeof add_features_field === 'function') {
            add_features_field();
            var all = form.querySelectorAll('select[name="social_media[]"]');
            fillRow(all[all.length - 1], platform, url);
        }
    });
    return true;
}

function cwRefreshJobPreview(jobs, industries) {
    var jobEl = document.getElementById('jobPreview');
    if (jobEl && jobs && jobs.length) {
        jobEl.innerHTML = jobs.map(function (j) {
            return '<span class="cw-tag">' + j + '</span>';
        }).join('');
    }
}

function cwRefreshPreviewsFromExtracted(d) {
    if (!d) return;
    cwRefreshProfilePreviews({
        full_name: [d.first_name, d.last_name].filter(Boolean).join(' '),
        email: d.email,
        phone: d.phone || d.whatsapp,
        whatsapp: d.whatsapp || d.phone,
        bio: d.bio,
        location: [d.city, d.state, d.country].filter(Boolean).join(', '),
        skills: d.skills || [],
        languages: d.languages || [],
        experiences: (d.work_experience || []).map(function (e) {
            return {
                company: e.company,
                department: e.department || '',
                designation: e.designation || e.position || e.title || '',
                start: e.start || '',
                end: e.end || '',
                currently_working: !!e.currently_working || !e.end
            };
        }),
        educations: (d.education_history || []).map(function (e) {
            return {
                level: e.level || d.education_level || '',
                degree: e.degree || '',
                year: e.year || ''
            };
        })
    });
    cwRefreshWorkTables({
        experiences: (d.work_experience || []).map(function (e) {
            return {
                company: e.company,
                department: e.department || '',
                designation: e.designation || e.position || e.title || '',
                start: e.start || '',
                end: e.end || '',
                currently_working: !!e.currently_working || !e.end
            };
        }),
        educations: (d.education_history || []).map(function (e) {
            return {
                level: e.level || d.education_level || '',
                degree: e.degree || '',
                year: e.year || ''
            };
        })
    });
    if (d.social_links && d.social_links.length) {
        fillSocialLinks(d.social_links);
        cwRefreshSocialPreview(d.social_links);
    }
    cwRefreshJobPreview(d.jobs || [], d.industries || []);
}

function fillBioField(value) {
    if (!value) return false;
    let ta = document.getElementById('candidate_bio');
    if (ta) {
        ta.value = value;
        return true;
    }
    return false;
}

function isoToDisplay(isoDate) {
    if (!isoDate) return null;
    let p = isoDate.split('-');
    if (p.length !== 3) return null;
    return p[2] + '-' + p[1] + '-' + p[0];
}

function showExtractionSummary(filled, updated, kept, notOnCv, fillFailed, source) {
    filled = Array.isArray(filled) ? filled : [];
    updated = Array.isArray(updated) ? updated : [];

    // Legacy 3-arg form: showExtractionSummary(filled, missing, 'Passport')
    if (typeof kept === 'string' && (notOnCv === undefined || notOnCv === null)) {
        source = kept;
        fillFailed = Array.isArray(updated) ? updated : [];
        notOnCv = [];
        kept = [];
        updated = [];
    } else if (typeof notOnCv === 'string') {
        source = notOnCv;
        fillFailed = Array.isArray(kept) ? kept : [];
        notOnCv = Array.isArray(updated) ? updated : [];
        kept = [];
        updated = [];
    } else if (Array.isArray(kept) && (fillFailed === undefined || fillFailed === null)) {
        fillFailed = Array.isArray(notOnCv) ? notOnCv : [];
        notOnCv = [];
    }

    kept = Array.isArray(kept) ? kept : [];
    notOnCv = Array.isArray(notOnCv) ? notOnCv : [];
    fillFailed = Array.isArray(fillFailed) ? fillFailed : [];
    source = source || 'Document';

    let card = document.getElementById('extractionSummary');
    if (!card) return;
    document.getElementById('summarySource').innerText = source + ' — Extraction Complete';

    let html = '';
    filled.forEach(function(f)  { html += '<span class="summary-tag filled-tag">&#10003; ' + f + '</span>'; });
    updated.forEach(function(f) { html += '<span class="summary-tag updated-tag">&#8635; ' + f + ' (updated)</span>'; });
    kept.forEach(function(f)    { html += '<span class="summary-tag kept-tag">= ' + f + ' (kept)</span>'; });
    document.getElementById('filledList').innerHTML = html;

    document.getElementById('notOnCvList').innerHTML = (notOnCv || []).map(function(f) {
        return '<span class="summary-tag kept-tag">○ ' + f + ' (not on CV)</span>';
    }).join('');

    document.getElementById('missingList').innerHTML = (fillFailed || []).map(function(f) {
        return '<span class="summary-tag missing-tag">&#9888; ' + f + ' (needs review)</span>';
    }).join('');

    document.getElementById('missingListTitle').innerText =
        (fillFailed && fillFailed.length) ? 'Needs review' : 'Not on CV / optional';

    card.classList.remove('d-none');
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function generateBioWithAI() {
    if (aiData && aiData.bio) {
        fillBioField(aiData.bio);
        return;
    }

    var btn = document.querySelector('[onclick="generateBioWithAI()"]');
    var ta = document.getElementById('candidate_bio');
    var currentBio = ta ? ta.value.trim() : '';
    var originalHtml = btn ? btn.innerHTML : '';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
    }

    fetch("{{ route('ai.generate.bio') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ current_bio: currentBio })
    })
    .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
    .then(function(r) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        if (!r.ok || r.data.error) {
            alert(r.data.message || r.data.error || 'Could not generate bio. Please try again.');
            return;
        }
        if (r.data.bio) {
            fillBioField(r.data.bio);
            if (typeof toastr !== 'undefined') {
                toastr.success('Bio generated — review and save when ready.', 'AI Bio');
            }
        }
    })
    .catch(function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        alert('Network error — could not generate bio.');
    });
}

/* ===== CV UPLOAD → AUTO-FILL ALL FIELDS ===== */
function uploadCV() {

    let file = document.getElementById("cvUpload").files[0];
    if (!file) return alert("Please select a CV file first.");

    let formData = new FormData();
    formData.append("cv", file);

    document.getElementById("aiLoader").classList.remove("d-none");

    fetch("{{ route('ai.parse.cv') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json"
        },
        body: formData
    })
    .then(function(res) {
        return res.text().then(function(text) {
            try { return { status: res.status, data: JSON.parse(text) }; }
            catch (e) { throw new Error('Server error ' + res.status + ': ' + text.substring(0, 300)); }
        });
    })
    .then(function(r) {

        console.log('[CV Parse] Response:', r);
        document.getElementById("aiLoader").classList.add("d-none");

        let res = r.data;

        if (res.error) {
            let msgs = { not_cv: 'This file does not appear to be a CV. Please upload your CV/resume.', unreadable: 'Cannot read text from this PDF. Please use a text-based (non-scanned) PDF.' };
            return alert(msgs[res.error] || 'AI extraction failed: ' + (res.message || res.error));
        }

        let d = res.data;
        if (!d) return alert('AI returned no data. Please try again.');

        var cvStoredPath = res.cv_stored_path || null;
        var cvFilename = res.cv_filename || file.name;

        if (window.OgsIntlPhone && typeof window.OgsIntlPhone.initAll === 'function') {
            var accountForm = document.getElementById('accountForm');
            if (accountForm) window.OgsIntlPhone.initAll(accountForm);
        }

        if (typeof window.cwRefreshSettingsSelects === 'function') {
            ['basicForm', 'skillForm', 'languageForm', 'jobForm', 'accountForm'].forEach(function (fid) {
                window.cwRefreshSettingsSelects(fid);
            });
        }

        let filled = [], updated = [], kept = [], notOnCv = [], fillFailed = [];

        function getInputVal(name) {
            let el = document.querySelector('[name="' + name + '"]');
            return el ? el.value.trim() : '';
        }
        function getSelectVal(name) {
            let el = document.querySelector('select[name="' + name + '"]');
            return el ? (el.value || '').trim() : '';
        }
        function normalize(v) { return String(v || '').toLowerCase().replace(/[^a-z0-9]/g, ''); }
        function isOptional(label) { return CV_OPTIONAL_FIELDS.indexOf(label) !== -1; }

        function smartInput(label, name, newVal) {
            if (!newVal) {
                if (!isOptional(label)) notOnCv.push(label);
                return;
            }
            let cur = getInputVal(name);
            let ok = fillInput(name, newVal);
            if (!ok) { fillFailed.push(label); return; }
            if (!cur) filled.push(label);
            else if (normalize(cur) === normalize(newVal)) kept.push(label);
            else updated.push(label);
        }
        function smartPhone(label, id, newVal) {
            if (!newVal) {
                if (!isOptional(label)) notOnCv.push(label);
                return;
            }
            let ok = fillPhoneField(id, newVal);
            if (!ok) smartInput(label, id === 'phone' ? 'phone' : 'whatsapp_number', newVal);
            else filled.push(label);
        }
        function smartSelect(label, name, newVal) {
            if (!newVal) {
                if (!isOptional(label)) notOnCv.push(label);
                return;
            }
            let cur = getSelectVal(name);
            let ok = fillSelect(name, newVal);
            if (!ok) { fillFailed.push(label); return; }
            if (!cur) filled.push(label);
            else if (normalize(cur) === normalize(newVal)) kept.push(label);
            else updated.push(label);
        }
        function smartMulti(label, name, newVals) {
            if (!newVals || !newVals.length) {
                if (!isOptional(label)) notOnCv.push(label);
                return;
            }
            fillMultiSelect(name, newVals) ? filled.push(label) : fillFailed.push(label);
        }

        smartInput('First Name',       'first_name',  d.first_name);
        smartInput('Last Name',        'last_name',   d.last_name);
        smartInput('Email',            'email',       d.email);
        smartPhone('Phone',            'phone',       d.phone || d.whatsapp);
        smartPhone('WhatsApp',         'whatsapp_number', d.whatsapp || d.phone);
        smartInput('Website',          'website',     d.website);
        smartSelect('Gender',          'gender',      d.gender);
        smartSelect('Marital Status',  'marital_status', d.marital_status);
        smartInput('Date of Birth',    'birth_date',  isoToDisplay(d.date_of_birth));
        smartInput('Passport Number',  'passport_number', d.passport_number);
        smartInput('Passport Issue',   'passport_issue_date', d.passport_issue_date);
        smartInput('Passport Expiry',  'passport_expiry_date', d.passport_expiry_date);
        smartInput('Place of Issue',   'place_of_issue', d.place_of_issue);
        smartInput('CNIC',             'cnic_number', d.cnic_number);
        smartSelect('Job Title',       'title',       d.titles ? d.titles[0] : null);
        smartSelect('Profession',      'profession',  d.profession);
        smartSelect('Experience Level','experience',  d.experience_level);
        smartSelect('Education Level', 'education',   d.education_level);
        smartSelect('Job Region',      'region',      d.job_preference_region);

        if (d.expected_salary) smartInput('Expected Salary', 'salary', d.expected_salary);
        if (d.salary_currency) smartSelect('Salary Currency', 'currency', d.salary_currency);

        // Location (basic info address)
        (function() {
            if (!d.country && !d.city) { notOnCv.push('Location (Country)'); return; }
            cwFillBasicLocation(d.country, d.state, d.city);
            filled.push('Country');
            if (d.state) filled.push('State / Region');
            if (d.city) filled.push('City');
        })();

        // Bio: only fill if currently empty
        (function() {
            let hasBio = false;
            let ta = document.getElementById('candidate_bio');
            if (ta && ta.value.trim()) {
                hasBio = true;
            }
            if (!hasBio && d.bio) { fillBioField(d.bio); filled.push('Professional Bio'); }
            else if (hasBio && d.bio) { kept.push('Professional Bio'); }
            else { notOnCv.push('Professional Bio'); }
        })();

        smartMulti('Skills',    'skills[]',    d.skills);
        if (d.languages && d.languages.length) {
            fillLookupMultiSelect('languages[]', d.languages, 'languages').then(function(ok) {
                ok ? filled.push('Languages') : fillFailed.push('Languages');
            });
        } else if (!isOptional('Languages')) {
            notOnCv.push('Languages');
        }
        smartMulti('Job Roles', 'jobs[]',      d.jobs);
        smartMulti('Industries','industries[]',d.industries);

        if (d.social_links && d.social_links.length) {
            fillSocialLinks(d.social_links);
            filled.push('Social Links (' + d.social_links.length + ')');
        } else if (d.website) {
            notOnCv.push('Social Links (GitHub/LinkedIn URLs not in CV text)');
        }

        let expCount = (d.work_experience && Array.isArray(d.work_experience)) ? d.work_experience.length : 0;
        if (expCount > 0) filled.push('Work Experience (' + expCount + ' jobs)');
        else notOnCv.push('Work Experience');

        let eduCount = (d.education_history && Array.isArray(d.education_history)) ? d.education_history.length : 0;
        if (eduCount > 0) filled.push('Education (' + eduCount + ' records)');
        else notOnCv.push('Education History');

        if (!d.passport_number) notOnCv.push('Passport Details');
        if (!d.expected_salary) notOnCv.push('Salary Expectation');
        notOnCv.push('Profile Photo');

        aiData = d;
        cwRefreshPreviewsFromExtracted(d);
        showExtractionSummary(filled, updated, kept, notOnCv, fillFailed, 'CV');

        // Keep the uploaded CV visible in its box so the user knows what they sent
        var cvBox = document.getElementById('cvText');
        if (cvBox) { cvBox.innerHTML = '&#10003; ' + file.name; cvBox.classList.add('upload-done'); }

        // Auto-save extracted data to the database
        fetch("{{ route('ai.save.cv') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                data: d,
                cv_stored_path: cvStoredPath,
                cv_filename: cvFilename
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(saveRes) {
            if (saveRes.error) {
                console.warn('[CV Save] error:', saveRes.error);
                toastr.warning('Profile filled in form — save it using the buttons below.', 'Review & Save');
                return;
            }
            if (saveRes.cv && saveRes.cv.name) {
                var cvBox = document.getElementById('cvText');
                if (cvBox) {
                    cvBox.innerHTML = '&#10003; ' + saveRes.cv.name;
                    cvBox.classList.add('upload-done');
                }
            }
            if (saveRes.profile) {
                cwRefreshProfilePreviews(saveRes.profile);
                cwRefreshWorkTables(saveRes.profile);
                if (saveRes.profile.social_links) {
                    cwRefreshSocialPreview(saveRes.profile.social_links);
                }
                if (saveRes.profile.country || saveRes.profile.district) {
                    cwFillBasicLocation(
                        saveRes.profile.country,
                        saveRes.profile.region,
                        saveRes.profile.district
                    );
                }
                if (saveRes.profile.languages && saveRes.profile.languages.length) {
                    fillLookupMultiSelect('languages[]', saveRes.profile.languages, 'languages');
                }
            }
            if (typeof window.cwUpdateCompletionBar === 'function') {
                window.cwUpdateCompletionBar(saveRes);
            }
            if (saveRes.saved && saveRes.saved.length) {
                toastr.success('Saved to profile — previews updated live.', 'Profile Updated!', { timeOut: 5000 });
            }
            if (saveRes.failed && saveRes.failed.length) {
                toastr.warning('Could not match: ' + saveRes.failed.join(', '), 'Needs Manual Entry');
            }
            console.log('[CV Extracted]', saveRes.extracted || d);
        })
        .catch(function(e) {
            console.warn('[CV Save] network error:', e);
        });

    })
    .catch(function(err) {
        document.getElementById("aiLoader").classList.add("d-none");
        console.error('[CV Parse] Error:', err);
        alert('Network error — CV extraction failed. Check browser console.');
    });
}

</script>
<script>
function triggerUpload(id) {
    document.getElementById(id).click();
}

// Switch the profile-photo widget from "existing photo" to "upload" mode.
// Defined here because this page (unlike additional-setting) did not include it,
// which left users who already had a photo unable to change it.
function UploadMode(param) {
    if (param === 'photo') {
        $('#photo-uploadMode').removeClass('d-none');
        $('#photo-oldMode').addClass('d-none');
    } else {
        $('#banner-uploadMode').removeClass('d-none');
        $('#banner-oldMode').addClass('d-none');
    }
}

// Instant profile-photo save: as soon as a photo is chosen, upload + persist it
// and swap every avatar on the page in real time (no need to submit the form).
document.addEventListener('change', function (e) {
    if (!e.target || !e.target.matches('input.profile-file-upload-input')) return;

    var file = e.target.files && e.target.files[0];
    if (!file) return;

    var fd = new FormData();
    fd.append('image', file);

    fetch("{{ route('candidate.profilePhoto') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: fd
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
    .then(function (res) {
        if (!res.ok || !res.d.success) {
            if (typeof toastr !== 'undefined') toastr.error(res.d.message || 'Photo upload failed');
            return;
        }
        // Bust the browser cache so the new image shows immediately
        var url = res.d.url + (res.d.url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
        document.querySelectorAll(
            '.profile-file-upload-image, .profile-image, .candidate-profile img, .avatar img'
        ).forEach(function (img) { img.src = url; });
        if (typeof toastr !== 'undefined') toastr.success(res.d.message || 'Profile photo updated');
        if (typeof window.cwUpdateCompletionBar === 'function') window.cwUpdateCompletionBar(res.d);
    })
    .catch(function () {
        if (typeof toastr !== 'undefined') toastr.error('Network error while uploading photo');
    });
});

// CV change — show newly selected file (saved checkmark appears after extract & save)
document.getElementById('cvUpload').addEventListener('change', function() {
    let file = this.files[0];
    if (!file) return;

    var cvBox = document.getElementById('cvText');
    cvBox.innerText = file.name;
    cvBox.classList.remove('upload-done');
});

// Passport change
// Passport change — show newly selected file (saved checkmark after extract)
document.getElementById('passportUpload').addEventListener('change', function() {
    let file = this.files[0];
    if (!file) return;

    var ppBox = document.getElementById('passportText');
    ppBox.innerText = file.name;
    ppBox.classList.remove('upload-done');
});
</script>
<script>
function showLoader(show = true){
    document.getElementById('aiLoader').classList.toggle('d-none', !show);
}

function runATS(cvText) {
    fetch("{{ route('ai.ats.score') }}", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            cv_text: cvText,
            job_description: getJobDescription()
        })
    })
    .then(res => res.json())
    .then(data => {

        document.getElementById('atsResult').classList.remove('d-none');

        // Score
        document.getElementById('scoreCircle').innerText = data.score + "%";

        // Skills
        fillList('matchedSkills', data.matched_skills);
        fillList('missingSkills', data.missing_skills);
        fillList('suggestions', data.suggestions);

    });
}

function fillList(id, items){
    let ul = document.getElementById(id);
    ul.innerHTML = '';
    items.forEach(i => {
        ul.innerHTML += `<li>${i}</li>`;
    });
}

function getJobDescription(){
    return $('select[name="jobs[]"]').val()?.join(', ') || 'General Job';
}

function uploadPassport() {
    let file = document.getElementById('passportUpload').files[0];
    if (!file) return alert("Please select a passport image.");

    let formData = new FormData();
    formData.append('passport', file);

    showLoader(true);

    fetch("{{ route('ai.parse.passport') }}", {
        method: "POST",
        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json' },
        body: formData
    })
    .then(function(res) {
        return res.text().then(function(text) {
            try { return { status: res.status, data: JSON.parse(text) }; }
            catch(e) { throw new Error('Server error ' + res.status + ': ' + text.substring(0, 300)); }
        });
    })
    .then(function(r) {

        console.log('[Passport Parse] Response:', r);
        showLoader(false);

        let res = r.data;
        if (res.error) return alert('Passport extraction failed: ' + (res.message || res.error));

        let e = res.extracted || {};
        let filled = [], missing = [];
        function t(label, result) { result ? filled.push(label) : missing.push(label); }

        let firstName = e.given_names ? e.given_names.split(' ')[0] : null;
        let lastName  = e.surname ? e.surname : (e.given_names && e.given_names.split(' ').length > 1 ? e.given_names.split(' ').slice(1).join(' ') : null);
        let genderVal = e.gender === 'M' ? 'male' : (e.gender === 'F' ? 'female' : e.gender);

        t('First Name',          fillInput('first_name', firstName));
        t('Last Name',           fillInput('last_name', lastName));
        t('Passport Number',     fillInput('passport_number', e.passport_number));
        t('Date of Birth',       fillInput('birth_date', isoToDisplay(e.date_of_birth)));
        t('Passport Issue Date', fillInput('passport_issue_date', isoToDisplay(e.date_of_issue)));
        t('Passport Expiry',     fillInput('passport_expiry_date', isoToDisplay(e.date_of_expiry)));
        t('Place of Issue',      fillInput('place_of_issue', e.place_of_issue));
        t('Gender',              fillSelect('gender', genderVal));
        t('CNIC / National ID',  fillInput('cnic_number', e.national_id));
        // nationality & cnic auto-saved to DB by backend; note if extracted
        if (e.nationality) filled.push('Nationality (saved to profile)');
        else missing.push('Nationality');

        if (res.conflicts && Object.keys(res.conflicts).length > 0) {
            let msg = 'Some passport values differ from your saved profile:\n';
            Object.entries(res.conflicts).forEach(function(entry) {
                msg += '• ' + entry[0] + ': saved="' + entry[1].db + '" vs passport="' + entry[1].ocr + '"\n';
            });
            alert(msg);
        }

        // The passport image was auto-saved to the Attachment section server-side.
        // Reflect it live in the passport preview + saved badge, just like the
        // profile photo, so the user sees their uploaded passport persisted there.
        if (res.attachment_url) {
            var pImg = document.getElementById('passportImagePreview');
            if (pImg) pImg.src = res.attachment_url + (res.attachment_url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
            var pBadge = document.getElementById('passportSavedBadge');
            if (pBadge) pBadge.classList.remove('d-none');
            var pDel = document.getElementById('deletePassportBtn');
            if (pDel) pDel.classList.remove('d-none');
            filled.push('Passport Image (saved to Attachments)');
            if (typeof toastr !== 'undefined') toastr.success('Passport image saved to Attachments.');
        }

        missing.push('Country (Location)', 'Profile Photo');
        showExtractionSummary(filled, [], [], [], missing, 'Passport');

        // Keep the uploaded passport visible in its box
        var ppBox = document.getElementById('passportText');
        if (ppBox) {
            ppBox.innerHTML = res.attachment_url
                ? '&#10003; Passport saved'
                : '&#10003; ' + file.name;
            ppBox.classList.add('upload-done');
        }

    })
    .catch(function(err) {
        showLoader(false);
        console.error('[Passport Parse] Error:', err);
        alert('Passport extraction failed: ' + err.message);
    });
}

</script>


    <script>
        /* ============================================================
           OGS accordion edit controls
           Same show/hide semantics as before (form visible = editing),
           but only ONE card's edit form is open at a time, and the
           active card gets an `.is-editing` class for styling.
           Form actions / submit logic are untouched.
           ============================================================ */
        window.OGS_SECTIONS = [
            { btn: 'basicToggleForm',         form: 'basicForm',         preview: 'basicInfoPreview' },
            { btn: 'jobToggleForm',           form: 'jobForm',           preview: 'jobPreview' },
            { btn: 'summaryToggleForm',       form: 'summaryForm',       preview: 'summaryPreview' },
            { btn: 'skillToggleForm',         form: 'skillForm',         preview: 'skillPreview' },
            { btn: 'languageToggleForm',      form: 'languageForm',      preview: 'languagePreview' },
            { btn: 'socialToggleForm',        form: 'socialForm',        preview: 'socialPreview' },
            { btn: 'accountToggleForm',       form: 'accountForm',       preview: 'accountPreview' },
            { btn: 'jobalertToggleForm',      form: 'jobalertForm',      preview: 'jobalertPreview' },
            { btn: 'profilePolicyToggleForm', form: 'profilePolicyForm', preview: 'profilePolicyPreview' },
            { btn: 'attachmentToggleForm',    form: 'attachmentForm',    preview: null },
        ];
        const OGS_SECTIONS = window.OGS_SECTIONS;

        function ogsCardOf(section) {
            const btn = document.getElementById(section.btn);
            return btn ? btn.closest('.glass-card') : null;
        }

        function ogsCloseSection(section) {
            const form = document.getElementById(section.form);
            const preview = section.preview ? document.getElementById(section.preview) : null;
            if (form) form.classList.add('tw-hidden');
            if (preview) preview.classList.remove('tw-hidden');
            const card = ogsCardOf(section);
            if (card) card.classList.remove('is-editing');
        }

        function ogsOpenSection(section) {
            const form = document.getElementById(section.form);
            const preview = section.preview ? document.getElementById(section.preview) : null;
            if (form) form.classList.remove('tw-hidden');
            if (preview) preview.classList.add('tw-hidden');
            const card = ogsCardOf(section);
            if (card) {
                card.classList.add('is-editing');
                if (card.scrollIntoView) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (typeof window.cwRefreshSettingsSelects === 'function') {
                setTimeout(function () { window.cwRefreshSettingsSelects(section.form); }, 60);
            }
            if (section.form === 'basicForm' && typeof window.cwBootBasicLocation === 'function') {
                setTimeout(function () { window.cwBootBasicLocation(); }, 120);
            }
            if (section.form === 'accountForm' && window.OgsIntlPhone && typeof window.OgsIntlPhone.initAll === 'function') {
                setTimeout(function () {
                    var el = document.getElementById('accountForm');
                    if (el) window.OgsIntlPhone.initAll(el);
                }, 80);
            }
        }

        window.ogsCloseSection = ogsCloseSection;
        window.ogsOpenSection = ogsOpenSection;

        OGS_SECTIONS.forEach(function(section) {
            const btn = document.getElementById(section.btn);
            if (!btn) return;
            btn.addEventListener('click', function() {
                const form = document.getElementById(section.form);
                const isOpen = form && !form.classList.contains('tw-hidden');
                if (isOpen) {
                    ogsCloseSection(section);
                } else {
                    OGS_SECTIONS.forEach(ogsCloseSection);
                    ogsOpenSection(section);
                }
            });
        });

        // Select2 sync + job payloads handled by candidate-settings-save.js

        function cwApplyCsrfToken(token) {
            if (!token) return;
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', token);
            document.querySelectorAll('input[name="_token"]').forEach(function (input) {
                input.value = token;
            });
        }

        function cwRefreshCsrfToken() {
            return fetch(@json(route('refresh.csrf', [], false)), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (data) {
                    if (data && data.token) cwApplyCsrfToken(data.token);
                })
                .catch(function () {});
        }

        document.querySelectorAll('form[action*="settings/update"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                cwApplyCsrfToken(document.querySelector('meta[name="csrf-token"]')?.content);
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                cwRefreshCsrfToken();
            }
        });

        /* ---- Scroll reveal (cards below the fold fade/slide in) ---- */
        (function() {
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce || !('IntersectionObserver' in window)) return;
            var cards = document.querySelectorAll('.glass-card, .ai-upload-card');
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
            cards.forEach(function(card) {
                // Only animate cards that start below the fold — no flash for visible ones
                if (card.getBoundingClientRect().top > vh * 0.9) {
                    card.classList.add('reveal');
                    io.observe(card);
                }
            });
        })();
    </script>
   <script>
    $(document).ready(function() {
        function syncSelect2($el) {
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.trigger('change.select2');
            }
        }

        function fetchStates(countryId, selectedStateId = null) {
            if (!countryId || countryId === "anywhere") return;

            $('#state').html('<option value="">Loading...</option>');
            syncSelect2($('#state'));

            $.ajax({
                url: "{{ route('candidate.getStates') }}",
                type: "GET",
                data: {
                    country_id: countryId
                },
                success: function(data) {
                    $('#state').html('<option value="">Select State</option>');

                    $.each(data.states, function(key, state) {
                        let selected = state.id == selectedStateId ? "selected" : "";
                        $('#state').append(
                            `<option value="${state.id}" ${selected}>${state.name}</option>`
                        );
                    });

                    syncSelect2($('#state'));

                    if (selectedStateId) {
                        fetchCities(selectedStateId,
                            "{{ old('city_id', optional($jobRequirement)->city_id) }}");
                    }
                }
            });
        }

        function fetchCities(stateId, selectedCityId = null) {
            if (!stateId) return;

            $('#city').html('<option value="">Loading...</option>');
            syncSelect2($('#city'));

            $.ajax({
                url: "{{ route('candidate.getCities') }}",
                type: "GET",
                data: {
                    state_id: stateId
                },
                success: function(data) {
                    $('#city').html('<option value="">Select City</option>');

                    $.each(data.cities, function(key, city) {
                        let selected = city.id == selectedCityId ? "selected" : "";
                        $('#city').append(
                            `<option value="${city.id}" ${selected}>${city.name}</option>`
                        );
                    });

                    syncSelect2($('#city'));
                }
            });
        }

        // Function to show/hide state and city based on country selection
        function toggleStateCityVisibility() {
            let countryValue = $('#country').val();
            if (countryValue === "anywhere") {
                $('#state').parent().hide();
                $('#city').parent().hide();
            } else {
                $('#state').parent().show();
                $('#city').parent().show();
            }
        }

        // Trigger on country change
        $('#country').on('change', function() {
            let countryId = $(this).val();
            toggleStateCityVisibility();
            fetchStates(countryId);
        });

        // Trigger on state change
        $('#state').on('change', function() {
            let stateId = $(this).val();
            fetchCities(stateId);
        });

        // Prepopulate on page load
        let selectedCountryId = "{{ old('country', optional($jobRequirement)->search_country_id) }}";
        let selectedStateId = "{{ old('state', optional($jobRequirement)->state_id) }}";
        let selectedCityId = "{{ old('district', optional($jobRequirement)->city_id) }}";

        if (selectedCountryId && selectedCountryId !== 'anywhere') {
            fetchStates(selectedCountryId, selectedStateId);
        } else if (selectedStateId && !$('#state option[value="' + selectedStateId + '"]').length) {
            fetchStates($('#country').val(), selectedStateId);
        } else if (selectedStateId && selectedCityId && !$('#city option[value="' + selectedCityId + '"]').length) {
            fetchCities(selectedStateId, selectedCityId);
        }

        // Run toggle function on page load to handle pre-selected "Anywhere"
        toggleStateCityVisibility();
    });
</script>

    {{-- Basic-Info location cascade (name-based, replaces Livewire) --}}
    <script>
    (function() {
        var statesUrl   = @json(route('candidate.getStatesByName', [], false));
        var citiesUrl   = @json(route('candidate.getCitiesByName', [], false));
        var currentCountryName = '';

        function appendLocationOption($select, value, selected) {
            if (!value) return false;
            var exists = false;
            $select.find('option').each(function() {
                if (cwLocationNamesMatch($(this).val(), value) || cwLocationNamesMatch($(this).text(), value)) {
                    exists = true;
                    if (selected) {
                        $select.val($(this).val());
                    }
                    return false;
                }
            });
            if (!exists) {
                $select.append(new Option(value, value, !!selected, !!selected));
                if (selected) {
                    $select.val(value);
                }
            }
            return true;
        }

        function loadStates(countryName, selectedState, selectedCity) {
            var $state = $('#basic_state');
            var $city  = $('#basic_city');
            currentCountryName = countryName || '';
            if (!countryName) {
                $state.html('<option value="">— Select State —</option>').prop('disabled', true);
                $city.html('<option value="">— Select City —</option>').prop('disabled', true);
                return;
            }
            $state.html('<option value="">Loading…</option>').prop('disabled', true);
            $city.html('<option value="">— Select City —</option>').prop('disabled', true);
            $.get(statesUrl, { country_name: countryName })
                .done(function(data) {
                    $state.html('<option value="">— Select State —</option>');
                    var matchedState = null;
                    $.each(data.states || [], function(i, s) {
                        var sel = cwLocationNamesMatch(s.name, selectedState) ? ' selected' : '';
                        if (sel) matchedState = s.name;
                        $state.append('<option value="' + s.name + '"' + sel + '>' + s.name + '</option>');
                    });
                    if (selectedState && !matchedState) {
                        appendLocationOption($state, selectedState, true);
                        matchedState = selectedState;
                    }
                    $state.prop('disabled', false);
                    syncBasicLocationSelect2();
                    if (matchedState || selectedState) {
                        loadCities(matchedState || selectedState, selectedCity || null, countryName);
                    } else {
                        finishLocationBoot();
                    }
                })
                .fail(function() {
                    $state.html('<option value="">— Select State —</option>');
                    if (selectedState) {
                        appendLocationOption($state, selectedState, true);
                    }
                    $state.prop('disabled', false);
                    syncBasicLocationSelect2();
                    if (selectedState) {
                        loadCities(selectedState, selectedCity || null, countryName);
                    } else {
                        finishLocationBoot();
                    }
                });
        }

        function loadCities(stateName, selectedCity, countryName) {
            var $city = $('#basic_city');
            if (!stateName) {
                $city.html('<option value="">— Select City —</option>').prop('disabled', true);
                return;
            }
            $city.html('<option value="">Loading…</option>').prop('disabled', true);
            $.get(citiesUrl, {
                state_name: stateName,
                country_name: countryName || currentCountryName || $('#basic_country').val() || ''
            })
                .done(function(data) {
                    $city.html('<option value="">— Select City —</option>');
                    var matchedCity = false;
                    $.each(data.cities || [], function(i, c) {
                        var sel = cwLocationNamesMatch(c.name, selectedCity) ? ' selected' : '';
                        if (sel) matchedCity = true;
                        $city.append('<option value="' + c.name + '"' + sel + '>' + c.name + '</option>');
                    });
                    if (selectedCity && !matchedCity) {
                        appendLocationOption($city, selectedCity, true);
                    }
                    $city.prop('disabled', false);
                    syncBasicLocationSelect2();
                    finishLocationBoot();
                })
                .fail(function() {
                    $city.html('<option value="">— Select City —</option>');
                    if (selectedCity) {
                        appendLocationOption($city, selectedCity, true);
                    }
                    $city.prop('disabled', false);
                    syncBasicLocationSelect2();
                    finishLocationBoot();
                });
        }

        function syncBasicLocationSelect2() {
            if (typeof window.cwRefreshSettingsSelects === 'function') {
                window.cwRefreshSettingsSelects('basicForm');
            }
        }

        function finishLocationBoot() {
            window.cwSkipLocationCascade = false;
        }

        window.cwLoadBasicLocation = loadStates;

        window.cwBootBasicLocation = function() {
            var preCountry = @json($basicLocationCountry ?? '');
            var preState   = @json($basicLocationState ?? '');
            var preCity    = @json($basicLocationCity ?? '');

            if (!preCountry) {
                return;
            }

            window.cwSkipLocationCascade = true;

            var $country = $('#basic_country');
            if ($country.length) {
                appendLocationOption($country, preCountry, true);
                syncBasicLocationSelect2();
            }

            loadStates(preCountry, preState || null, preCity || null);
        };

        $(document).ready(function() {
            $('#basic_country').on('change', function() {
                if (window.cwSkipLocationCascade) return;
                loadStates($(this).val(), null, null);
            });
            $('#basic_state').on('change', function() {
                if (window.cwSkipLocationCascade) return;
                loadCities($(this).val(), null, currentCountryName || $('#basic_country').val() || '');
            });
        });
    })();
    </script>

    <script>
        function updateSalary() {
            const slider = document.querySelector(".salaryRange");
            const display = document.querySelector(".salaryDisplay");
            display.textContent = new Intl.NumberFormat().format(slider.value);

        }

        function updateSalary1() {
            const slider = document.querySelector(".salaryRange1");
            const display = document.querySelector(".salaryDisplay1");
            display.textContent = new Intl.NumberFormat().format(slider.value);

        }
    </script>




    <script>
        // Instant attachment save — passport & license images behave exactly like
        // the profile photo: chosen file is previewed immediately, uploaded in the
        // background, and persisted to the DB with no full-form submit.
        function previewImage(input, imageElementId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(imageElementId).src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        document.querySelectorAll('.attachment-instant-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                const field      = this.dataset.field;
                const previewId  = this.dataset.preview;
                const badgeId    = this.dataset.badge;
                const deleteId   = this.dataset.delete;

                // Instant local preview
                previewImage(this, previewId);

                // Show chosen file name on the label
                const lbl = this.nextElementSibling;
                if (lbl && lbl.classList.contains('custom-file-label')) lbl.innerText = file.name;

                const fd = new FormData();
                fd.append('field', field);
                fd.append('image', file);

                fetch("{{ route('candidate.attachmentImage') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    })
                    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, d: d }; }); })
                    .then(function(res) {
                        if (!res.ok || !res.d.success) {
                            if (typeof toastr !== 'undefined') toastr.error(res.d.message || 'Upload failed');
                            return;
                        }
                        // Cache-bust so the saved file shows immediately
                        var url = res.d.url + (res.d.url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
                        document.getElementById(previewId).src = url;
                        var badge = document.getElementById(badgeId);
                        if (badge) badge.classList.remove('d-none');
                        var delBtn = document.getElementById(deleteId);
                        if (delBtn) delBtn.classList.remove('d-none');
                        if (typeof toastr !== 'undefined') toastr.success(res.d.message || 'Saved');
                    })
                    .catch(function() {
                        if (typeof toastr !== 'undefined') toastr.error('Network error while uploading');
                    });
            });
        });

        document.querySelectorAll('.attachment-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Remove this image?')) return;

                const field       = this.dataset.field;
                const previewId   = this.dataset.preview;
                const badgeId     = this.dataset.badge;
                const placeholder = this.dataset.placeholder;
                const self        = this;

                const fd = new FormData();
                fd.append('field', field);

                fetch("{{ route('candidate.attachmentImage.delete') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    })
                    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, d: d }; }); })
                    .then(function(res) {
                        if (!res.ok || !res.d.success) {
                            if (typeof toastr !== 'undefined') toastr.error(res.d.message || 'Delete failed');
                            return;
                        }
                        document.getElementById(previewId).src = placeholder;
                        var badge = document.getElementById(badgeId);
                        if (badge) badge.classList.add('d-none');
                        self.classList.add('d-none');
                        if (typeof toastr !== 'undefined') toastr.success(res.d.message || 'Removed');
                    })
                    .catch(function() {
                        if (typeof toastr !== 'undefined') toastr.error('Network error while deleting');
                    });
            });
        });
    </script>
    <script>
        window.cwSettingsLookupUrl = @json(url('/candidate/settings/lookup'));
        window.cwSettingsSaveConfig = {
            saveUrl: @json(route('candidate.settingUpdate', [], false)),
            settingsUrl: @json(url('/candidate/settings')),
            csrfRefreshUrl: @json(route('refresh.csrf', [], false)),
            contactTitle: @json(__('your_contact_information')),
        };
        window.cwModalRoutes = {
            experienceDelete: @json(route('candidate.experiences.destroy', ['experience' => '__ID__'], false)),
            educationDelete: @json(route('candidate.educations.destroy', ['education' => '__ID__'], false)),
        };
    </script>
    <script src="{{ asset('js/candidate-settings-select2.js') }}?v={{ @filemtime(public_path('js/candidate-settings-select2.js')) ?: '1' }}"></script>
    <script src="{{ asset('js/candidate-settings-save.js') }}?v={{ @filemtime(public_path('js/candidate-settings-save.js')) ?: '1' }}"></script>
    <script src="{{ asset('js/candidate-settings-modals.js') }}?v={{ @filemtime(public_path('js/candidate-settings-modals.js')) ?: '1' }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select21').not('.cw-ms-select').not('.cw-static-select').select2({ theme: 'bootstrap4', width: '100%' });
        });
        window.addEventListener('render-select2', function() {
            $('.select21').not('.cw-ms-select').not('.cw-static-select').select2({ theme: 'bootstrap4', width: '100%' });
        });
    </script>
    @stack('js')
    @if (app()->getLocale() == 'ar')
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ">
        </script>
    @endif
    <script>
        //init datepicker
        $("#available_id_date").attr("autocomplete", "off");

        availableStatus('{{ old('status', $candidate->status) }}');

        $('#available_status').on('change', function() {
            availableStatus(this.value);
        });

        function availableStatus(status) {
            if (status == 'available_in') {
                $('#available_in_status').removeClass('d-none');
            } else {
                $('#available_in_status').addClass('d-none');
            }
        }


        function UploadMode(param) {
            if (param === 'photo') {
                $('#photo-uploadMode').removeClass('d-none');
                $('#photo-oldMode').addClass('d-none');
            } else {
                $('#banner-uploadMode').removeClass('d-none');
                $('#banner-oldMode').addClass('d-none');
            }
        }
        //init datepicker
        $("#date").attr("autocomplete", "off");
        //init datepicker
        $('#date').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });

        $("#passportIssueDate").attr("autocomplete", "off");
        //init datepicker
        $('#passportIssueDate').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });
        $("#passportExpiryDate").attr("autocomplete", "off");
        //init datepicker
        $('#passportExpiryDate').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });
    </script>
    <script>
        function AccountDelete() {
            if (confirm("Are you sure ??") == true) {
                $('#AccountDelete').submit();
            } else {
                return false;
            }
        }

        function resumeDelete() {
            if (confirm("Are you sure ?") == true) {
                $('#resumeForm').submit();
            } else {
                return false;
            }
        }

        function editResume(id, name, size) {
            $('#resume_id_input').val(id);
            $('#resume_name_input').val(name);
            $('#resume_file_size').html(size);
            $('#resumeEditModal').modal('show');
        }
        $('.cv-remove-image').on('click', function() {
            $('.resume-file-upload-input').replaceWith($('.resume-file-upload-input').clone());
            $('.resume-file-upload-content').hide();
            $('.cv-image-upload-wrap').show();
            $('.resume-file-upload-input').val('');
        })

        function resumeManageReadURL(input, type) {
            if (type == 'add') {
                var fileName = document.querySelector('#resume_add_input').files[0].name;
                var fileSize = document.querySelector('#resume_add_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_add_input').files[0].type;
            } else {
                var fileName = document.querySelector('#resume_edit_input').files[0].name;
                var fileSize = document.querySelector('#resume_edit_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_edit_input').files[0].type;
            }
            $('.resume_selected_file_name').html(fileName);
            $('.resume_selected_file_size').html(fileSize.toFixed(4));
            $('.resume_selected_file_type').html(fileType);
            if (input.files && input.files[0]) {
                console.log(input.className)
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (input.className === 'profile-file-upload-input') {
                        $('.profile-image-upload-wrap').hide();
                        $('.profile-file-upload-image').attr('src', e.target.result);
                        $('.profile-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'banner-file-upload-input') {
                        $('.banner-image-upload-wrap').hide();
                        $('.banner-file-upload-image').attr('src', e.target.result);
                        $('.banner-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'resume-file-upload-input') {
                        $('.cv-image-upload-wrap').hide();
                        $('.resume-file-upload-content.none').show();
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                $('.profile-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.profile-file-upload-input').replaceWith($('.profile-file-upload-input').clone());
                    $('.profile-file-upload-content').hide();
                    $('.profile-file-upload-image').attr('src', '');
                    $('.profile-image-upload-wrap').show();
                })
                $('.banner-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.banner-file-upload-input').replaceWith($('.banner-file-upload-input').clone());
                    $('.banner-file-upload-content').hide();
                    $('.banner-file-upload-image').attr('src', '');
                    $('.banner-image-upload-wrap').show();
                })
            }
        }
        setTimeout(function() {
            {{ session()->forget('type') }}
        }, 10000);
    </script>

    @if (config('templatecookie.map_show'))
    {{-- Leaflet  --}}
    @include('map::set-edit-leafletmap', ['lat' => $candidate->lat, 'long' => $candidate->long])


    <!-- ============== google map ========= -->
    <x-website.map.google-map-check />
    <script>
        function initMap() {
            var mapEl = document.getElementById("google-map");
            if (!mapEl) return;

            var token = "{{ $setting->google_map_key }}";
            var oldlat = {!! $candidate->lat ? $candidate->lat : $setting->default_lat !!};
            var oldlng = {!! $candidate->long ? $candidate->long : $setting->default_long !!};
            const map = new google.maps.Map(mapEl, {
                zoom: 7,
                center: {
                    lat: oldlat,
                    lng: oldlng
                },
            });
            const image =
                "https://gisgeography.com/wp-content/uploads/2018/01/map-marker-3-116x200.png";
            const beachMarker = new google.maps.Marker({
                draggable: true,
                position: {
                    lat: oldlat,
                    lng: oldlng
                },
                map,
                // icon: image
            });
            google.maps.event.addListener(map, 'click',
                function(event) {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');

                    pos = event.latLng
                    beachMarker.setPosition(pos);
                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`
                    ).then((data) => {
                        if (data.data.error_message) {
                            toastr.error(data.data.error_message, 'Error!');
                            toastr.error('Your location is not set because of a wrong API key.', 'Error!');
                        }

                        const total = data.data.results.length;
                        let amount = '';
                        if (total > 1) {
                            amount = total - 2;
                        }
                        const result = data.data.results.slice(amount);
                        let country = '';
                        let region = '';
                        for (let index = 0; index < result.length; index++) {
                            const element = result[index];
                            if (element.types[0] == 'country') {
                                country = element.formatted_address;
                            }
                            if (element.types[0] == 'administrative_area_level_1') {
                                const str = element.formatted_address;
                                const first = str.split(',').shift()
                                region = first;
                            }
                        }
                        var form = new FormData();
                        form.append('lat', lat);
                        form.append('lng', lng);
                        form.append('country', country);
                        form.append('region', region);
                        form.append('exact_location', data.data.results[0].formatted_address);

                        setLocationSession(form);

                        $('.location_country').text(country);
                        $('.location_full_address').text(data.data.results[0].formatted_address ||
                            'No address found');
                        $('.loader_position').addClass('d-none');
                        $('.location_secion').removeClass('d-none');
                    })
                });
            google.maps.event.addListener(beachMarker, 'dragend',
                function() {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');

                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`
                    ).then((data) => {
                        if (data.data.error_message) {
                            toastr.error(data.data.error_message, 'Error!');
                            toastr.error('Your location is not set because of a wrong API key.', 'Error!');
                        }

                        const total = data.data.results.length;
                        let amount = '';
                        if (total > 1) {
                            amount = total - 2;
                        }
                        const result = data.data.results.slice(amount);
                        let country = '';
                        let region = '';
                        for (let index = 0; index < result.length; index++) {
                            const element = result[index];
                            if (element.types[0] == 'country') {
                                country = element.formatted_address;
                            }
                            if (element.types[0] == 'administrative_area_level_1') {
                                const str = element.formatted_address;
                                const first = str.split(' ').shift()
                                region = first;
                            }
                        }
                        var form = new FormData();
                        form.append('lat', lat);
                        form.append('lng', lng);
                        form.append('country', country);
                        form.append('region', region);
                        form.append('exact_location', data.data.results[0].formatted_address);

                        setLocationSession(form);

                        $('.location_country').text(country);
                        $('.location_full_address').text(data.data.results[0].formatted_address ||
                            'No address found');
                        $('.loader_position').addClass('d-none');
                        $('.location_secion').removeClass('d-none');
                    })
                });
            // Search
            var input = document.getElementById('searchInput');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

            let country_code = '{{ current_country_code() }}';
            if (country_code) {
                var options = {
                    componentRestrictions: {
                        country: country_code
                    }
                };
                var autocomplete = new google.maps.places.Autocomplete(input, options);
            } else {
                var autocomplete = new google.maps.places.Autocomplete(input);
            }

            autocomplete.bindTo('bounds', map);
            var infowindow = new google.maps.InfoWindow();
            var marker = new google.maps.Marker({
                map: map,
                anchorPoint: new google.maps.Point(0, -29)
            });
            autocomplete.addListener('place_changed', function() {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
            });
        }
        window.initMap = initMap;
    </script>
    {{-- Only load the Google Maps JS when a key is configured. Loading it with
         an empty/invalid key floods the console with NoApiKeys / InvalidKey. --}}
    @if (!empty($setting->google_map_key))
        @php
            $scr = 'https://maps.googleapis.com/maps/api/js?key='
                . $setting->google_map_key
                . '&callback=initMap&libraries=places,geometry';
        @endphp
        <script src="{{ $scr }}" async defer></script>
    @endif
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            if ($.fn && typeof $.fn.tooltip === 'function') {
                $("[data-toggle=tooltip]").tooltip();
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                document.querySelectorAll('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            }
        })
    </script>

    <script>
        $('#pills-setting-tab').on('click', function() {
            setTimeout(function() {
                if (typeof map !== 'undefined' && map && typeof map.resize === 'function') {
                    map.resize();
                }
                if (typeof leaflet_map !== 'undefined' && leaflet_map && typeof leaflet_map.invalidateSize === 'function') {
                    leaflet_map.invalidateSize(true);
                }
            }, 200);
        })
    </script>
    <script>
        $(".new-select").select2({ // minimumResultsForSearch: Infinity,
        });
    </script>

    <script>
        function toggleCustomInput(select) {
            const customInput = document.getElementById('custom_product');
            if (!customInput) return;
            if (select.value === 'custom') {
                customInput.style.display = 'block';
                customInput.value = ''; // Clear the custom input if 'Custom Option' is selected
            } else {
                customInput.style.display = 'none';
                customInput.value = ''; // Clear the custom input if another option is selected
            }
        }
    </script>
    <script type="text/javascript">
        // feature field
        function add_features_field() {
            $("#multiple_feature_part").append(`
        <div class="col-12 custom-select-padding">
            <div class="d-flex tw-items-center">
                <div class="d-flex mborder">
                    <div class="position-relative">
                        <select
                            class="w-100-p border-0 rt-selectactive-2 form-control" name="social_media[]">
                            <option value="" class="d-none" disabled selected>{{ __('select_one') }}</option>
                            <option value="facebook">{{ __('facebook') }}</option>
                            <option value="twitter">{{ __('twitter') }}</option>
                            <option value="instagram">{{ __('instagram') }}</option>
                            <option value="youtube">{{ __('youtube') }}</option>
                            <option value="linkedin">{{ __('linkedin') }}</option>
                            <option value="pinterest">{{ __('pinterest') }}</option>
                            <option value="reddit">{{ __('reddit') }}</option>
                            <option value="github">{{ __('github') }}</option>
                            <option value="other">{{ __('other') }}</option>
                        </select>
                    </div>
                    <div class="w-100">
                        <input class="border-0" type="url" name="url[]" id="" placeholder="{{ __('profile_link_url') }}...">
                    </div>
                </div>
                <div class="tw-ms-2">
                    <button class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center" type="button" id="remove_item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#18191C" stroke-width="1.5" stroke-miterlimit="10"/>
                            <path d="M15 9L9 15" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 15L9 9" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `);
            $(".rt-selectactive-2").select2({ // minimumResultsForSearch: Infinity,
            });
        }

        $(document).on("click", "#remove_item", function() {
            $(this).parent().parent().parent('div').remove();
        });
    </script>
@endsection
     
