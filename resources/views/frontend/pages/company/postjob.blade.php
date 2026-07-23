{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.company.layout.app')

@section('css')
        <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
        <x-map.leaflet.map_links />
        <x-map.leaflet.autocomplete_links />
        @include('map::links')
        <style>
            .seeker-settings-page .cw-settings-layout { align-items: start; }
            .seeker-settings-page .cw-chapter-nav {
                z-index: 30;
                align-self: start;
                max-height: calc(100vh - 6rem);
                overflow-y: auto;
                overflow-x: hidden;
            }
            .seeker-settings-page .cw-settings-content {
                min-width: 0;
                width: 100%;
                overflow: visible;
                position: relative;
            }
            .seeker-settings-page #company-job-form { min-width: 0; }
            .seeker-settings-page .benefits-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                width: 100%;
                max-width: 100%;
            }
            .seeker-settings-page .benefits-tags label {
                position: relative;
                display: inline-flex;
                align-items: center;
                margin: 0;
                cursor: pointer;
            }
            .seeker-settings-page .benefits-tags input {
                position: absolute;
                opacity: 0;
                width: 0;
                height: 0;
                margin: 0;
            }
            .seeker-settings-page .benefits-tags label span {
                display: inline-block;
                font-size: 0.8125rem;
                color: #475569;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 0.5rem 0.75rem;
                white-space: nowrap;
                transition: border-color 0.15s, background 0.15s, color 0.15s;
            }
            .seeker-settings-page .benefits-tags input:checked + span {
                color: #1d4ed8;
                background: #eff6ff;
                border-color: #2563eb;
            }
            .seeker-settings-page .select2-container { max-width: 100%; }
            .seeker-settings-page .ck-editor__editable_inline { min-height: 280px; }
            .seeker-settings-page .mymap { border-radius: 10px; }
            .seeker-settings-page .radio-check {
                border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;
                cursor: pointer; transition: border-color 0.15s, background 0.15s;
            }
            .seeker-settings-page .radio-check.checked {
                border-color: #2563eb; background: #eff6ff;
            }
            .seeker-settings-page .radio-check input[type="radio"] { display: none; }
            .seeker-settings-page .btn-ui {
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.625rem 1.25rem; font-weight: 600; font-size: 0.875rem;
                color: #fff; background: #2563eb; border: none; border-radius: 8px;
            }
            .seeker-settings-page .btn-ui:hover { background: #1d4ed8; color: #fff; }
            @media (max-width: 991.98px) {
                .seeker-settings-page .cw-chapter-nav {
                    position: static;
                    max-height: none;
                    display: flex;
                    flex-wrap: nowrap;
                    overflow-x: auto;
                    gap: 0.35rem;
                }
                .seeker-settings-page .cw-chapter-nav__label { display: none; }
                .seeker-settings-page .cw-chapter-link {
                    flex: 0 0 auto;
                    white-space: nowrap;
                    font-size: 0.75rem;
                }
            }
        </style>
    @endsection

@section('title')
    {{ __('post_job') }}
@endsection

@section('main')
    <div class="dashboard-wrapper seeker-settings-page">
        <div class="container">
            <div class="dashboard-right">

                <x-website.company.employer-page-header
                    :title="__('post_job')"
                    subtitle="Create a professional job listing and attract top talent worldwide."
                />

                <x-website.company.job-ai-upload />

                <div class="cw-settings-layout">
                    <x-website.company.job-form-nav />

                    <div class="cw-settings-content">
                        <form id="company-job-form" action="{{ route('company.job.store') }}" method="POST" class="rt-from">
                            @csrf
                            @if ($errors->any())
                                <div class="alert alert-danger tw-mb-4" role="alert">
                                    <strong>{{ __('please_fix_the_following_errors') }}:</strong>
                                    <ul class="tw-mb-0 tw-mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div id="section-job-basic" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('basic_information') }}</h3>
                                <div class="row">
                                    {{-- <div class="col-lg-8 rt-mb-20">
                                        <x-forms.label name="job_title" :required="true" class="tw-text-sm tw-mb-2" />
                                        <input value="{{ old('title') }}" name="title"
                                            class="form-control @error('title') is-invalid @enderror" type="text"
                                            placeholder="{{ __('job_title') }}" id="m">
                                        @error('title')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div> --}}
                                    <div class="col-lg-8 rt-mb-20">
                                        <x-forms.label name="job_title" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select name="title" class="cw-ms-select form-control"
                                            data-cw-lookup="professions"
                                            data-cw-value="text"
                                            data-cw-tags="1"
                                            data-placeholder="Search job title…">
                                            @php $selectedTitle = old('title'); @endphp
                                            @if ($selectedTitle)
                                                <option value="{{ $selectedTitle }}" selected>{{ $selectedTitle }}</option>
                                            @endif
                                        </select>
                                        @error('title')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 rt-mb-20 col-md-4">
                                        <x-forms.label name="Industry" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-ms-select form-control @error('category_id') is-invalid @enderror"
                                            name="category_id"
                                            data-cw-lookup="industries"
                                            data-placeholder="Search industry…">
                                            @php
                                                $selectedCategoryId = old('category_id');
                                                $selectedCategory = $selectedCategoryId
                                                    ? ($jobCategories->firstWhere('id', (int) $selectedCategoryId) ?? null)
                                                    : null;
                                            @endphp
                                            @if ($selectedCategory)
                                                <option value="{{ $selectedCategory->id }}" selected>{{ $selectedCategory->name }}</option>
                                            @endif
                                        </select>
                                        @error('category_id')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-8 rt-mb-20 col-md-8">
                                        <x-forms.label name="tags" :required="false" class="tw-text-sm tw-mb-2">
                                            ({{ __('saerch_or_write_tag_and_hit_enter') }})
                                        </x-forms.label>

                                        <select id="tagsSelect"
                                            class="cw-ms-select form-control @error('tags') is-invalid @enderror"
                                            name="tags[]" multiple
                                            data-cw-lookup="tags" data-cw-tags="1" data-placeholder="Search or add tags…">
                                            @foreach ($tags as $tag)
                                                <option
                                                    {{ old('tags') ? (in_array($tag->id, old('tags')) ? 'selected' : '') : '' }}
                                                    value="{{ $tag->id }}">{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('tags')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 rt-mb-20 col-md-4">
                                        <x-forms.label name="job_role" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-ms-select form-control @error('role_id') is-invalid @enderror"
                                            name="role_id" data-cw-lookup="job_roles" data-placeholder="Select job role…">
                                            @php
                                                $selectedRoleId = old('role_id');
                                                $selectedRole = $selectedRoleId
                                                    ? ($roles->firstWhere('id', (int) $selectedRoleId) ?? null)
                                                    : null;
                                            @endphp
                                            @if ($selectedRole)
                                                <option value="{{ $selectedRole->id }}" selected>{{ $selectedRole->name }}</option>
                                            @endif
                                        </select>
                                        @error('role_id')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div id="section-job-salary" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('salary') }}</h3>
                                <div class="tw-flex tw-gap-5 mb-3">
                                    <div
                                        class="ll-radio tw-flex tw-items-center tw-border tw-border-gray-200 tw-rounded tw-ps-1">
                                        <input checked onclick="salaryModeChange('range')" id="salary_rangee" type="radio"
                                            value="range" name="salary_mode" class="tw-scale-150">
                                        <label for="salary_rangee"
                                            class="tw-w-full tw-py-4 tw-ms-2 tw-text-sm tw-font-medium">{{ __('salary_range') }}</label>
                                    </div>
                                    <div
                                        class="ll-radio tw-flex tw-items-center tw-border tw-border-gray-200 tw-rounded tw-ps-1">
                                        <input onclick="salaryModeChange('custom')" id="custom_salary" type="radio"
                                            value="custom" name="salary_mode" class="tw-scale-150">
                                        <label for="custom_salary"
                                            class="tw-w-full tw-py-4 tw-ms-2 tw-text-sm tw-font-medium">{{ __('custom_salary') }}</label>
                                    </div>



                                        <div class="rt-mb-20 col-md-8" >
                                            <x-forms.label name="Currency Code" :required="true" class="tw-text-sm tw-mb-2" />
                                            <div class="position-relative">
                                                <select name="currency" id="currency" class="cw-static-select form-control w-100">
                                                    @php
                                                        $currencies = ['USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)', 'PKR' => 'PKR (₨)',
                                                                       'JPY' => 'JPY (¥)', 'AED' => 'AED (د.إ)', 'SAR' => 'SAR (﷼)', 'QAR' => 'QAR (ر.ق)',
                                                                       'KWD' => 'KWD (د.ك)', 'OMR' => 'OMR (﷼)', 'BHD' => 'BHD (ب.د)', 'TRY' => 'TRY (ب.د)'];
                                                    @endphp
                                                    @foreach ($currencies as $code => $label)
                                                    <option value="{{ $code }}" >
                                                        {{ $label }}
                                                    </option>
                                                    @endforeach
                                                </select>

                                                @error('currency')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                </div>

                                <div class="row">
                                    <div class="rt-mb-20 col-md-8 d-none" id="custom_salary_part">
                                        <x-forms.label name="custom_salary" :required="true" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <input value="{{ old('custom_salary', 'Competitive') }}" name="custom_salary"
                                                class="form-control @error('custom_salary') is-invalid @enderror"
                                                type="text" placeholder="{{ __('job_title') }}" id="m">
                                            @error('custom_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="rt-mb-20 col-md-4 salary_range_part">
                                        <x-forms.label name="min_salary" :required="false" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <input step="0.01" value="{{ old('min_salary', '50.00') }}"
                                                class="form-control @error('min_salary') is-invalid @enderror"
                                                name="min_salary" type="number" placeholder="{{ __('min_salary') }}"
                                                id="m">
                                            {{-- <div class="usd">{{ $currency_symbol }}</div> --}}
                                            @error('min_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="rt-mb-20 col-md-4 salary_range_part">
                                        <x-forms.label name="max_salary" :required="false" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <input step="0.01" value="{{ old('max_salary', '100.00') }}"
                                                class="form-control @error('max_salary') is-invalid @enderror"
                                                name="max_salary" type="number" placeholder="{{ __('max_salary') }}"
                                                id="m">
                                            {{-- <div class="usd">{{ $currency_symbol }}</div> --}}
                                            @error('max_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 rt-mb-20 col-md-6">
                                        <x-forms.label name="{{ __('salary_type') }}" :required="true"
                                            class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-static-select form-control @error('salary_type') is-invalid @enderror"
                                            name="salary_type">
                                            @foreach ($salary_types as $type)
                                                <option {{ old('salary_type') == $type->id ? 'selected' : '' }}
                                                    value="{{ $type->id }}">
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('salary_type')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                </div>
                            </div>


                            <div id="section-job-requirements" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('advance_information') }}</h3>
                                <div class="row">
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="education" :required="false" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-static-select form-control @error('education') is-invalid @enderror"
                                            name="education">
                                            @foreach ($educations as $education)
                                                <option {{ old('education') == $education->id ? 'selected' : '' }}
                                                    value="{{ $education->id }}">
                                                    {{ $education->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('education')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <input type="checkbox" id="education_limit" name="education_limit"
                                            value="1">
                                        Limit applications based on the selected value
                                    </div>
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="experience" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-static-select form-control @error('experience') is-invalid @enderror"
                                            name="experience">
                                            @foreach ($experiences as $experience)
                                                <option {{ old('experience') == $experience->id ? 'selected' : '' }}
                                                    value="{{ $experience->id }}">
                                                    {{ $experience->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('experience')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <input type="checkbox" id="experience_limit" name="experience_limit"
                                            value="1">
                                        Limit applications based on the selected value
                                    </div>
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="job_type" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="cw-static-select form-control @error('job_type') is-invalid @enderror"
                                            name="job_type">
                                            @foreach ($job_types as $job_type)
                                                <option {{ old('job_type') == $job_type->id ? 'selected' : '' }}
                                                    value="{{ $job_type->id }}">
                                                    {{ $job_type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('job_type')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <x-forms.label name="Minimum Age" for="min_age" />
                                        <select name="min_age" class="cw-static-select form-control" id="min_age">
                                            <option value="" selected disabled>Select Minimum Age</option>
                                            @for ($i = 18; $i <= 60; $i++)
                                                <option {{ $i == old('min_age') ? 'selected' : '' }}
                                                    value="{{ $i }}">{{ $i }}</option>
                                            @endfor

                                        </select>
                                        @error('min_age')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ __($message) }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <x-forms.label name="Maximum Age" for="max_age" />
                                        <select name="max_age"
                                            class="cw-static-select form-control @error('max_age') is-invalid @enderror"
                                            id="max_age">
                                            <option value="" selected disabled>Select Maximum Age</option>
                                            @for ($i = 18; $i <= 60; $i++)
                                                <option {{ $i == old('max_age') ? 'selected' : '' }}
                                                    value="{{ $i }}">{{ $i }}</option>
                                            @endfor
                                        </select>
                                        @error('max_age')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ __($message) }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">

                                        <input type="checkbox" id="age_limit" name="age_limit" value="1">
                                        Limit applications based on the selected value

                                    </div>
                                    <div class="col-md-6">
                                        <x-forms.label name="gender" for="gender" />
                                        <select name="gender" class="cw-static-select form-control" id="gender">
                                            <option value="" selected disabled>Select Gender </option>
                                            <option value="male">Male </option>
                                            <option value="female">Female </option>
                                            <option value="both">Both </option>

                                        </select>
                                        @error('gender')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ __($message) }}</strong>
                                            </span>
                                        @enderror
                                        <input type="checkbox" id="gender_limit" name="gender_limit" value="1">
                                        Limit applications based on the selected value

                                    </div>

                                    <div class="col-lg-6 col-md-6 rt-mb-20">
                                        <x-forms.label name="vacancies" :required="true" class="tw-text-sm tw-mb-2" />
                                        <input value="{{ old('vacancies', 1) }}" name="vacancies" type="text"
                                            placeholder="{{ __('vacancies') }}"
                                            class="form-control @error('vacancies') is-invalid @enderror" id="vacancies">
                                        @error('vacancies')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 col-md-6 rt-mb-20">
                                        <x-forms.label name="deadline_expired" :required="true"
                                            class="tw-text-sm tw-mb-2" />
                                        <div class="fromGroup">
                                            <div class="form-control-icon date datepicker">
                                                <input value="{{ old('deadline') }}" name="deadline"
                                                    class="form-control @error('deadline') is-invalid @enderror"
                                                    type="text" value="{{ old('deadline') ? old('deadline') : '' }}"
                                                    id="date" placeholder="d/m/y">
                                                <span class="input-group-addon has-badge">
                                                    <span @error('deadline') rt-mr-12 @enderror>
                                                        <x-svg.calendar-icon />
                                                    </span>
                                                </span>
                                                @error('deadline')
                                                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="tw-text-sm tw-font-medium tw-text-red-500">
                                            {{ __('maximum_deadline_limit') }}:
                                            {{ $setting->job_deadline_expiration_limit }} {{ __('days') }}
                                        </div>
                                    </div>
                                </div>
                            <div class="row mt-2" id="section-job-location">
                                @if (config('templatecookie.map_show'))
                                    <div class="col-12 rt-mb-15">
                                        @php
                                            $map = $setting->default_map;
                                        @endphp
                                        <div class="location-wrapper">
                                            <div class="row">
                                                <div class="col-12">
                                                    <h2>
                                                        {{ __('location') }} <span class="text-danger">*</span>
                                                        <small class="h6">
                                                            ({{ __('click_to_add_a_pointer') }})
                                                        </small>
                                                    </h2>
                                                </div>
                                                <div class="col-md-12 col-sm-12 rt-mb-24">
                                                    <x-website.map.map-warning />

                                                    <div id="google-map-div"
                                                        class="{{ $map == 'google-map' ? '' : 'd-none' }}">
                                                        <input id="searchInput" class="mapClass" type="text"
                                                            placeholder="{{ __('enter_location') }}">
                                                        <div class="map mymap" id="google-map"></div>
                                                    </div>
                                                    <div class="{{ $map == 'leaflet' ? '' : 'd-none' }}">
                                                        <input type="text" autocomplete="off" id="leaflet_search"
                                                            placeholder="{{ __('enter_city_name') }}"
                                                            class="full-width" />
                                                        <br>
                                                        <div id="leaflet-map"></div>
                                                    </div>
                                                    @error('location')
                                                        <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-12 mt-4 custom-checkbox-wrap">
                                                    <label class="main tw-text-sm"
                                                        for="remoteWork">{{ __('fully_remote_position') }}-<span
                                                            class="tw-font-medium">{{ __('worldwide') }}</span>
                                                        <input type="checkbox" name="is_remote" id="remoteWork"
                                                            value="1" {{ old('is_remote') ? 'checked' : '' }}>
                                                        <span class="custom-checkbox"></span>
                                                    </label>
                                                    <input type="checkbox" name="is_remote" id="remoteWork"
                                                        value="1" {{ old('is_remote') ? 'checked' : '' }}>
                                                </div>

                                                <div class="col-12 mt-4">
                                                    @php
                                                        $session_location = session()->get('location');
                                                        $session_country =
                                                            $session_location &&
                                                            array_key_exists('country', $session_location)
                                                                ? $session_location['country']
                                                                : '-';
                                                        $session_exact_location =
                                                            $session_location &&
                                                            array_key_exists('exact_location', $session_location)
                                                                ? $session_location['exact_location']
                                                                : '-';
                                                    @endphp
                                                    <div class="card-footer row mt-4 border-0">
                                                        <span>
                                                            <img src="{{ asset('frontend/assets/images/loader.gif') }}"
                                                                alt="loading" width="50px" height="50px"
                                                                class="loader_position d-none">
                                                        </span>
                                                        <div class="location_secion">
                                                            {{ __('country') }}: <span
                                                                class="location_country">{{ $session_country }}</span>
                                                            </br>
                                                            {{ __('full_address') }}: <span
                                                                class="location_full_address">{{ $session_exact_location }}</span>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <x-forms.label name="location" :required="true" class="tw-text-sm tw-mb-2" />
                                    <div class="card-body pt-0">
                                        <div>
                                            @livewire('country-state-city', ['row' => true])
                                            @error('location')
                                                <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                            @enderror
                                            <input type="checkbox" id="city_limit" name="city_limit" value="1">
                                            Limit applications based on the selected value
                                        </div>
                                    </div>
                                @endif
                            </div>
                                </div>
                            </div>
                            <div id="section-job-benefits" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('benefits') }}</h3>
                                <div class="benefits-tags" id="benefit_list">
                                    @foreach ($benefits as $benefit)
                                        <label for="benefit_{{ $benefit->id }}">
                                            <input
                                                {{ old('benefits') ? (in_array($benefit->id, old('benefits')) ? 'checked' : '') : '' }}
                                                type="checkbox" id="benefit_{{ $benefit->id }}" name="benefits[]"
                                                value="{{ $benefit->id }}">
                                            <span>{{ $benefit->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('benefits')
                                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                                @enderror

                                <div class="mt-3">
                                    <a onclick="showHideCreateBenefit()" href="javascript:void(0)"
                                        class="text-decoration-underline">{{ __('create_new') }} {{ __('benefit') }}</a>

                                    <div class="d-flex tw-justify-between tw-gap-2 mt-3 d-none" id="create_benefit">
                                        <input value="{{ old('title') }}" name="new_benefit"
                                            class="form-control @error('title') is-invalid @enderror" type="text"
                                            placeholder="{{ __('benefit') }}" id="m">

                                        <button onclick="createBenefit()" type="button"
                                            class="btn btn-primary rt-mr-10">
                                            <span class="button-content-wrapper ">
                                                <span class="button-text">
                                                    {{ __('create') }} {{ __('benefit') }}
                                                </span>
                                                <span class="button-icon align-icon-right">
                                                    <i class="ph ph-plus"></i>
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div id="section-job-skills" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('skills') }}</h3>
                                <select id="skills" name="skills[]"
                                    class="cw-ms-select form-control @error('skills') is-invalid @enderror" multiple
                                    data-cw-lookup="skills" data-cw-tags="1" data-placeholder="Search or add skills…">
                                    @foreach ($skills as $skill)
                                        <option
                                            {{ old('skills') ? (in_array($skill->id, old('skills')) ? 'selected' : '') : '' }}
                                            value="{{ $skill->id }}">{{ $skill->name }}</option>
                                    @endforeach
                                </select>
                                @error('skills')
                                    <span class="invalid-feedback d-block" role="alert">{{ __($message) }}</span>
                                @enderror
                                </div>
                            </div>
                            @if ($dynamicInputs->isNotEmpty())
                            <div class="glass-card form-section-anchor">
                                <div class="glass-card-body" id="dynamic-inputs">
                                <h3>{{ __('Additional Fields') }}</h3>
                                <div class="row">
                                @foreach ($dynamicInputs as $index => $input)
                                    <div class="col-lg-6 mb-3">
                                        <label
                                            for="dynamic_inputs_{{ $input->id }}">{{ ucwords(str_replace('_', ' ', $input->attribute_name)) }}</label>
                                        <input type="text" name="dynamic_inputs[{{ $index }}][value]"
                                            class="form-control" value="{{ $input->attribute_value }}"
                                            placeholder="{{ ucwords(str_replace('_', ' ', $input->attribute_name)) }}">
                                        @error('dynamic_inputs.' . $index . '.value')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <input type="hidden" name="dynamic_inputs[{{ $index }}][id]"
                                            value="{{ $input->id }}">
                                    </div>
                                @endforeach
                                </div>
                                </div>
                            </div>
                            @endif
                            <div id="section-job-description" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>
                                    {{ __('job_description') }}
                                    <span class="form-label-required text-danger">*</span>
                                </h3>
                                <p class="text-muted tw-text-sm tw-mb-2">{{ __('At least 30 characters of plain text (formatting does not count).') }}</p>
                                <div class="col-md-12">
                                    <textarea id="image_ckeditorx" class="form-control  @error('description') is-invalid @enderror" name="description">{{ old('description') }}
                                    </textarea>
                                    @error('description')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                </div>
                            </div>

                            <div class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>
                                    العنوان بالعربية
                                    <small class="text-muted fw-normal" style="font-size:13px">(Arabic Job Title — Optional)</small>
                                </h3>
                                <div class="col-md-12">
                                    <input type="text"
                                           name="title_ar"
                                           value="{{ old('title_ar') }}"
                                           class="form-control @error('title_ar') is-invalid @enderror"
                                           placeholder="مثال: مهندس برمجيات"
                                           dir="rtl"
                                           style="text-align:right">
                                    @error('title_ar')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                </div>
                            </div>

                            <div class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>
                                    وصف الوظيفة بالعربية
                                    <small class="text-muted fw-normal" style="font-size:13px">(Arabic Description — Optional)</small>
                                </h3>
                                <div class="col-md-12">
                                    <textarea name="description_ar"
                                              class="form-control @error('description_ar') is-invalid @enderror"
                                              rows="6"
                                              dir="rtl"
                                              style="text-align:right; font-family:inherit"
                                              placeholder="اكتب وصف الوظيفة باللغة العربية هنا...">{{ old('description_ar') }}</textarea>
                                    @error('description_ar')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                </div>
                            </div>

                            {{-- Additional Questions --}}
                            @if (currentCompany()->question_feature_enable)
                                <div x-data="appQuestion()" x-init="select2Alpine"
                                    class="glass-card form-section-anchor">
                                    <div class="glass-card-body">
                                    <h3>{{ __('add_screening_questions') }}</h3>
                                    <div class="row">
                                        <div class="rt-mb-20">
                                            <div class="col-lg-12">
                                                <div x-show="isAddingNewQuestion" class="tw-flex justify-content-between">
                                                    <label class="tw-text-sm tw-mb-2 mb-2" for="for">
                                                        {{ __('create_new_screening_question') }}
                                                    </label>
                                                    <a x-show="isAddingNewQuestion" href="#"
                                                        @click.prevent="isAddingNewQuestion = false">
                                                        {{ __('choose_from_existing_question') }}
                                                    </a>
                                                </div>
                                                <div x-show="!isAddingNewQuestion"
                                                    class="tw-flex justify-content-between">
                                                    <label class="tw-text-sm tw-mb-2 mb-2" for="for">
                                                        {{ __('choose_from_existing_question') }}
                                                    </label>
                                                    <a href="#" x-show="!isAddingNewQuestion"
                                                        @click.prevent="isAddingNewQuestion = true"
                                                        href="#">{{ __('create_new_screening_question') }}</a>
                                                </div>
                                                <input x-show="isAddingNewQuestion" value="" x-model="newQuestion"
                                                    class="form-control " type="text" placeholder="Add Question">
                                            </div>
                                            <div x-show="isAddingNewQuestion"
                                                class="tw-flex tw-gap-5 mb-3 flex justify-content-between tw-mt-4">
                                                <div class="tw-flex justify-between ">
                                                    <div
                                                        class="ll-radio tw-flex tw-items-center tw-border tw-border-gray-200 tw-rounded tw-ps-1 tw-mr-4">
                                                        <label class="mt-2">
                                                            <input x-model="newQuestionSave" class="tw-scale-150"
                                                                type="checkbox" style="margin-right: 10px">
                                                            {{ __('save_for_letter') }}
                                                        </label>
                                                    </div>
                                                    <div
                                                        class="ll-radio tw-flex tw-items-center tw-border tw-border-gray-200 tw-rounded tw-ps-1">
                                                        <label class="mt-2">
                                                            <input x-model="isRequired" class="tw-scale-150"
                                                                type="checkbox" style="margin-right: 10px">
                                                            {{ __('candidate_must_answer') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div>
                                                    <button @click.prevent="addQuestion" type="button"
                                                        class="btn btn-primary"> {{ __('save') }} </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div x-show="isAddingNewQuestion == false" class="q-select">
                                        <select id="questionSelect" multiple="multiple" x-ref="select"
                                            data-placeholder="Select Questions" name="companyQuestions[]"
                                            class="select2-taggable form-control">
                                            <option></option>
                                            @foreach ($questions as $question)
                                                <option value="{{ $question->id }}"> {{ $question->title }}
                                                    {{ $question->required ? '(required)' : '' }} </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div x-show="selectedQuestions.length">
                                        <h4 class="f-size-18 ft-wt-5 rt-mb-20 lh-1 mt-4">
                                            {{ __('selected_screening_questions') }}</h4>
                                        <ul>
                                            <template x-for="question in selectedQuestions">
                                                <div class="tw-flex justify-content-between my-2">
                                                    <li class="flex-grow-1"
                                                        x-text="question.required  ? question.title+' (required)' : question.title ">
                                                    </li>
                                                    <div class="cursor-pointer f" style="color:red;">
                                                        <svg @click="remove(question.id)" width="20" height="20"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            class="w-6 h-6">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>

                                                    </div>
                                                </div>
                                            </template>
                                        </ul>
                                    </div>
                                    </div>
                                </div>
                            @endif

                            <div id="section-job-apply" class="glass-card form-section-anchor">
                                <div class="glass-card-body">
                                <h3>{{ __('apply_job_on') }}</h3>
                            <div class="row tw-mb-0">
                                <div class="col-12">
                                    <div class="applied-job-on">
                                        <div class="row">
                                            <div id="applied_on_app"
                                                class="radio-check col-lg-4 d-flex {{ old('apply_on') === 'app' ? 'checked' : '' }}"
                                                onclick="RadioChecked('app')">
                                                <input type="radio" {{ old('apply_on') === 'app' ? 'checked' : '' }}
                                                    checked name="apply_on" value="app" id="app-app">
                                                <label for="app-app">
                                                    <h4 class="d-inline-block">{{ __('onn') }}
                                                        {{ config('app.name') }}</h4>
                                                    <p class="tw-mb-0">{{ __('candidate_will_apply_job_using') }}
                                                        {{ config('app.name') }} &
                                                        {{ __('all_application_will_show_on_your_dashboard') }}.</p>
                                                </label>
                                            </div>
                                            <div id="applied_on_custom_url"
                                                class="radio-check col-lg-4 d-flex {{ old('apply_on') === 'custom_url' ? 'checked' : '' }}"
                                                onclick="RadioChecked('custom_url')">
                                                <input type="radio"
                                                    {{ old('apply_on') === 'custom_url' ? 'checked' : '' }}
                                                    name="apply_on" value="custom_url" id="app-custom_url">
                                                <label for="app-custom_url">
                                                    <h4 class="d-inline-block">{{ __('external_platform') }}</h4>
                                                    <p class="tw-mb-0">
                                                        {{ __('candidate_apply_job_on_your_website_all_application_on_your_own_website') }}.
                                                    </p>
                                                </label>
                                            </div>
                                            <div id="applied_on_email"
                                                class="radio-check col-lg-4 d-flex {{ old('apply_on') === 'email' ? 'checked' : '' }}"
                                                onclick="RadioChecked('email')">
                                                <input type="radio" {{ old('apply_on') === 'email' ? 'checked' : '' }}
                                                    name="apply_on" value="email" id="app-email">
                                                <label for="app-email">
                                                    <h4 class="d-inline-block">{{ __('on_your_email') }}</h4>
                                                    <p class="tw-mb-0">
                                                        {{ __('candidate_apply_job_on_your_email_address_and_all_application_in_your_email') }}.
                                                    </p>
                                                </label>
                                            </div>
                                            <!-- apply_on end-->
                                            <div class="col-12 tw-mt-2 d-none" id="apply_on_custom_url">
                                                <x-forms.label name="website_url" :required="true" />
                                                <div class="fromGroup has-icon2">
                                                    <div class="form-control-icon">
                                                        <input value="{{ old('apply_url') }}" name="apply_url"
                                                            class="form-control @error('apply_url') is-invalid @enderror"
                                                            type="url" placeholder="{{ __('website') }}">
                                                        <div class="icon-badge-2 @error('apply_url') mt-n-11 @enderror">
                                                            <x-svg.link-icon />
                                                        </div>
                                                        @error('apply_url')
                                                            <span class="error invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 tw-mt-2 d-none" id="apply_on_email">
                                                <x-forms.label name="email_address" :required="true" />
                                                <div class="fromGroup has-icon2">
                                                    <div class="form-control-icon">
                                                        <input value="{{ old('apply_email') }}" name="apply_email"
                                                            class="form-control @error('apply_email') is-invalid @enderror"
                                                            type="email" placeholder="{{ __('email_address') }}">
                                                        <div class="icon-badge-2 @error('apply_email') mt-n-11 @enderror">
                                                            <x-svg.envelope-icon />
                                                        </div>
                                                        @error('apply_email')
                                                            <span class="error invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                </div>
                            </div>
                            <div class="glass-card">
                                <div class="glass-card-body text-end">
                                <button type="submit" class="btn-ui">
                                    <i class="ph-arrow-right"></i>
                                    {{ __('post_job') }}
                                </button>
                            </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

    @section('frontend_scripts')
        <script>window.cwSettingsLookupUrl = @json(url('/company/job-form/lookup'));</script>
        <script src="{{ asset('js/candidate-settings-select2.js') }}?v={{ @filemtime(public_path('js/candidate-settings-select2.js')) ?: '1' }}"></script>
        <script src="{{ asset('js/company-job-form.js') }}?v={{ @filemtime(public_path('js/company-job-form.js')) ?: '1' }}"></script>
        <script>
            function toggleCustomInput(select) {
                const customInput = document.getElementById('custom_product');
                if (!customInput) return;
                if (select.value === 'custom') {
                    customInput.style.display = 'block';
                    customInput.value = '';
                } else {
                    customInput.style.display = 'none';
                    customInput.value = '';
                }
            }
        </script>
        @livewireScripts
        <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
        <script defer src="{{ asset('backend/js/alpine.min.js') }}"></script>


        <script>
            function appQuestion() {
                return {
                    allQuestions: @json($questions),
                    selectedQuestions: [],
                    selectedQuestionsIds: [],
                    newQuestion: '',
                    isAddingNewQuestion: false,
                    newQuestionSave: true,
                    isRequired: false,
                    addQuestion: function() {

                        if (!this.newQuestion) return;


                        axios.post('/company/questions', {
                            newQuestion: this.newQuestion,
                            newQuestionSave: this.newQuestionSave,
                            isRequired: this.isRequired

                        }).then((response) => {
                            this.selectedQuestions.push(response.data);
                            this.allQuestions.push(response.data);

                            this.selectedQuestionsIds.push(response.data.id);
                            var optionValue = response.data.id;
                            var optionText = response.data.title;
                            if (response.data.required) {

                                optionText += '(required)'
                            }
                            var newOption = new Option(optionText, optionValue, false, true);
                            this.select2 = $(this.$refs.select).select2();

                            this.select2.append(newOption).trigger('change');

                        })


                        this.newQuestion = "";
                        this.newQuestionSave = true;
                        this.isRequired = false;

                    },
                    remove: function(idToRemove) {
                        this.selectedQuestionsIds = this.selectedQuestionsIds.filter((id) => {
                            return id != idToRemove;
                        })
                        this.selectedQuestions = this.selectedQuestions.filter((ques) => {
                            return ques.id != idToRemove;
                        })
                        this.select2 = $(this.$refs.select).select2();
                        this.select2.val(this.selectedQuestionsIds);
                        this.select2.trigger('change');

                    }
                }
            }

            function select2Alpine() {

                this.select2 = $(this.$refs.select).select2();
                this.select2.on("select2:select", (event) => {
                    var values = [];
                    var old_values = [];

                    // copy all option values from selected
                    $(event.currentTarget).find("option:selected").each(function(i, selected) {
                        values[i] = $(selected).val();
                    });

                    this.selectedQuestionsIds = values;
                    console.log(this.allQuestions);
                    var items = [];

                    this.allQuestions.forEach((item) => {
                        if (values.includes(item.id.toString())) {
                            items.push(item);
                        }

                    });

                    this.selectedQuestions = items;



                });
                this.select2.on("select2:unselect", (event) => {
                    var values = [];
                    $(event.currentTarget).find("option:selected").each(function(i, selected) {
                        values[i] = $(selected).val();
                    });

                    this.selectedQuestionsIds = values;
                    console.log(values);
                    var items = [];

                    this.allQuestions.forEach((item) => {
                        console.log(values);
                        console.log(item.id);
                        if (values.includes(item.id.toString())) {

                            items.push(item);
                        }

                    });

                    this.selectedQuestions = items;


                });
            }
        </script>


        {{-- CKEditor initialized in frontend/partials/scripts.blade.php (#image_ckeditorx) --}}
        @if (app()->getLocale() == 'ar')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                                                                                    "></script>
        @endif
        @if (config('templatecookie.map_show'))
            @php $activeMap = $map ?? $setting->default_map; @endphp
            @if ($activeMap == 'leaflet')
                @include('map::set-leafletmap')
            @elseif ($activeMap == 'google-map' && filled($setting->google_map_key))
                @include('map::set-googlemap')
            @endif
        @endif


        <script>
            var max_days = '{{ $setting->job_deadline_expiration_limit }}'

            //init datepicker
            $("#date").attr("autocomplete", "off");
            //init datepicker
            $('#date').off('focus').datepicker({
                format: 'dd-mm-yyyy',
                startDate: '0d',
                endDate: `+${max_days}d`,
                isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
                language: "{{ app()->getLocale() }}",
            }).on('click',
                function() {
                    $(this).datepicker('show');
                }
            );
        </script>


        <script>
            var salary_mode = "{!! old('salary_mode') !!}";

            if (salary_mode) {
                salaryModeChange(salary_mode);
            }

            function salaryModeChange(param) {
                var value = param;

                if (value === 'range') {
                    $('#custom_salary_part').addClass('d-none');
                    $('.salary_range_part').removeClass('d-none');
                    $('#salary_rangee').prop('checked', true)
                    $('#custom_salary').prop('checked', false)
                } else {
                    $('#custom_salary_part').removeClass('d-none');
                    $('.salary_range_part').addClass('d-none');
                    $('#salary_rangee').prop('checked', false)
                    $('#custom_salary').prop('checked', true)
                }
            }

            function RadioChecked(param) {
                var value = param;
                if (value === 'email') {
                    $('#applied_on_email').addClass('checked');
                    $('#apply_on_custom_url').addClass('d-none');
                    $('#apply_on_email').removeClass('d-none');
                    $('#applied_on_app').removeClass('checked');
                    $('#applied_on_custom_url').removeClass('checked');
                }
                if (value === 'custom_url') {
                    $('#applied_on_custom_url').addClass('checked');
                    $('#apply_on_email').addClass('d-none');
                    $('#apply_on_custom_url').removeClass('d-none');
                    $('#applied_on_app').removeClass('checked');
                    $('#applied_on_email').removeClass('checked');
                }
                if (value === 'app') {
                    $('#applied_on_app').addClass('checked');
                    $('#applied_on_email').removeClass('checked');
                    $('#applied_on_custom_url').removeClass('checked');
                    $('#apply_on_email').addClass('d-none');
                    $('#apply_on_custom_url').addClass('d-none');
                }
            }
            $('.radio-check').on('click', function() {
                $('input:radio', this).prop('checked', true);
            });

            if ($('#app-app').is(':checked')) {
                $('#applied_on_app').addClass('checked');
            }
            if ($('#app-custom_url').is(':checked')) {
                $('#apply_on_custom_url').removeClass('d-none');
            }
            if ($('#app-email').is(':checked')) {
                $('#apply_on_email').removeClass('d-none');
            }

            var apply_url = "{!! $errors->first('apply_url') !!}";
            var apply_url1 = "{!! old('apply_email') !!}";
            var apply_email = "{!! $errors->first('apply_email') !!}";
            var apply_email1 = "{!! old('apply_email') !!}";

            if (apply_url) {
                $('#apply_on_custom_url').removeClass('d-none');
            }
            if (apply_url1) {
                $('#apply_on_custom_url').removeClass('d-none');
            }
            if (apply_email) {
                $('#apply_on_email').removeClass('d-none');
            }
            if (apply_email1) {
                $('#apply_on_email').removeClass('d-none');
            }


            function showHideCreateBenefit() {
                $('#create_benefit').toggleClass('d-none');
            }

            function createBenefit() {
                var benefit = $('input[name="new_benefit"]').val();

                if (benefit == '') {
                    alert('Please enter benefit name');
                    return false;
                }

                axios.post("/job/benefits/create", {
                    benefit: benefit
                }).then((response) => {
                    var data = response.data;

                    if (data.length && typeof data == 'string') {
                        return Swal.fire('Error', data, 'error');
                    }

                    $('#benefit_list').append(`<label for="benefit_${data.id}">
                    <input type="checkbox" id="benefit_${data.id}" name="benefits[]" value="${data.id}">
                    <span>${data.name}</span>
                </label>`);

                    $('input[name="new_benefit"]').val('');
                }).catch((err) => {
                    this.errors = err.response.data.errors;
                });
            }
        </script>
        <script>

function handleJobAI(select) {

    let slug = select.value;

    if (!slug) return;

    /* ========================
       FETCH AI TEMPLATE
    ======================== */
    fetch(`/ai/job-template-by-slug/${slug}`)
    .then(res => res.json())
    .then(data => {

        if (!data) return;

        /* ================= CATEGORY AUTO SELECT ================= */
        let categorySelect = document.getElementById('categorySelect');

        if (categorySelect && data.category_id) {
            categorySelect.value = data.category_id;
            $(categorySelect).trigger('change');
        }

        /* ================= SALARY ================= */
        document.querySelector('[name="min_salary"]').value = data.min_salary || '';
        document.querySelector('[name="max_salary"]').value = data.max_salary || '';

        /* ================= EXPERIENCE ================= */
        let exp = document.getElementById('experienceSelect');
        if (exp && data.experience) {
            exp.value = data.experience;
            $(exp).trigger('change');
        }

        /* ================= TAGS ================= */
        let tagSelect = $('#tagsSelect');
        tagSelect.val(null).trigger('change');

        if (data.tags) {
            data.tags.forEach(tag => {
                let option = new Option(tag, tag, true, true);
                tagSelect.append(option).trigger('change');
            });
        }

        /* ================= SKILLS ================= */
        let skillSelect = $('#skills');
        skillSelect.val(null).trigger('change');

        if (data.skills) {
            data.skills.forEach(skill => {
                let option = new Option(skill, skill, true, true);
                skillSelect.append(option).trigger('change');
            });
        }

        /* ================= CKEDITOR ================= */
        if (window.editorInstance && data.description) {
            window.editorInstance.setData(data.description);
        }

    });

}

</script>
    @endsection
