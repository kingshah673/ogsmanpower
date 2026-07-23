{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agency.layout.app')


@section('title')
    {{ __('edit_job') }}
@endsection

@section('main')
    <div class="dashboard-wrapper py-5">
    <div class="container-fluid">
        <div class="row justify-content-center">

            <div class="col-12 col-xl-10">

                {{-- Premium Header --}}
                <div class="card border-0 shadow-lg rounded-4 mb-4 overflow-hidden">
                    <div class="p-4 text-white"
                         style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                        <h3 class="fw-semibold mb-1">
                            {{ __('Agency Settings') }}
                        </h3>
                        <p class="mb-0 opacity-75">
                            Manage your agency profile, branding, social links, contact information and security settings.
                        </p>
                    </div>
                </div>

                {{-- Main Content Card --}}
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                {{-- Sidebar --}}
                {{-- <x-website.agency.sidebar /> --}}
                <div class="col-lg-980">
                    <div class="dashboard-right">
                        {{-- <div class="dashboard-right-header">
                            <span class="sidebar-open-nav">
                                <i class="ph-list"></i>
                            </span>
                        </div> --}}
                        <h2 class="tw-text-2xl tw-font-medium tw-text-[#18191C] tw-mb-8">
                            {{ __('edit_job') }}
                        </h2>
                        <form action="{{ route('agency.job.update', $job->slug) }}" method="POST" class="rt-from">
                            @csrf
                            @method('PUT')
                            <div class="post-job-item rt-mb-15">
                                <div class="row">
                                    {{-- <div class="col-lg-8 rt-mb-20">
                                        <x-forms.label name="job_title" :required="true" class="tw-text-sm tw-mb-2" />
                                        <input value="{{ $job->title }}" name="title"
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
                                            @if (old('title', $job->title ?? null))
                                                <option value="{{ old('title', $job->title) }}" selected>{{ old('title', $job->title) }}</option>
                                            @endif
                                        </select>
                                        @error('title')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 rt-mb-20 col-md-4">
                                        <x-forms.label name="Industry" :required="true" />
                                        <select
                                            class="cw-ms-select form-control @error('category_id') is-invalid @enderror"
                                            name="category_id"
                                            data-cw-lookup="industries"
                                            data-placeholder="Search industry…">
                                            @php
                                                $selectedCategoryId = old('category_id', $job->category_id ?? null);
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
                                                    @foreach ($job->tags as $job_tag)
                                                    {{ $job_tag->id == $tag->id ? 'selected' : '' }} @endforeach
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
                                                $selectedRoleId = old('role_id', $job->role_id ?? null);
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
                            <div class="post-job-item rt-mb-15">
                                <h4 class="f-size-18 ft-wt-5 rt-mb-20 lh-1">{{ __('salary') }}</h4>
                                <div class="tw-flex tw-gap-5 mb-3">
                                    <div
                                        class="ll-radio tw-flex tw-items-center tw-pl-4 tw-border tw-border-gray-200 tw-rounded tw-ps-1">
                                        <input checked onclick="salaryModeChange('range')" id="salary_rangee" type="radio"
                                            value="range" name="salary_mode" class="tw-scale-150">
                                        <label for="salary_rangee"
                                            class="tw-w-full tw-py-4 tw-ms-2 tw-text-sm tw-font-medium">{{ __('salary_range') }}</label>
                                    </div>
                                    <div
                                        class="ll-radio tw-flex tw-items-center tw-pl-4 tw-border tw-border-gray-200 tw-ps-1">
                                        <input onclick="salaryModeChange('custom')" id="custom_salary" type="radio"
                                            value="custom" name="salary_mode" class="tw-scale-150">
                                        <label for="custom_salary"
                                            class="tw-w-full tw-py-4 tw-ms-2 tw-text-sm tw-font-medium">{{ __('custom_salary') }}</label>
                                    </div>
                                    <div class="rt-mb-20 col-md-8" >
                                        <x-forms.label name="Currency Code" :required="true" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <select name="currency" id="currency" class="block w-28 border-gray-300 rounded-md shadow-sm">
                                                @php
                                                    $currencies = ['USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)', 'PKR' => 'PKR (₨)',
                                                                   'JPY' => 'JPY (¥)', 'AED' => 'AED (د.إ)', 'SAR' => 'SAR (﷼)', 'QAR' => 'QAR (ر.ق)',
                                                                   'KWD' => 'KWD (د.ك)', 'OMR' => 'OMR (﷼)', 'BHD' => 'BHD (ب.د)'];
                                                @endphp
                                                @foreach ($currencies as $code => $label)
                                                <option value="{{ $code }}"{{ $job?->currency == $code ? 'selected' : '' }} >
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
                                            <input value="{{ old('custom_salary', $job->custom_salary) }}"
                                                name="custom_salary"
                                                class="form-control @error('custom_salary') is-invalid @enderror"
                                                type="text" id="m">
                                            @error('custom_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="rt-mb-20 col-md-4 salary_range_part">
                                        <x-forms.label name="min_salary" :required="false" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <input step="0.01" value="{{ $job->min_salary }}"
                                                class="form-control @error('min_salary') is-invalid @enderror"
                                                name="min_salary" type="number" placeholder="{{ __('min_salary') }}"
                                                id="m">
                                            <div class="usd">{{ $currency_symbol }}</div>
                                            @error('min_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="rt-mb-20 col-md-4 salary_range_part">
                                        <x-forms.label name="max_salary" :required="false" class="tw-text-sm tw-mb-2" />
                                        <div class="position-relative">
                                            <input step="0.01" value="{{ $job->max_salary }}"
                                                class="form-control @error('max_salary') is-invalid @enderror"
                                                name="max_salary" type="number" placeholder="{{ __('max_salary') }}"
                                                id="m">
                                            <div class="usd">{{ $currency_symbol }}</div>
                                            @error('max_salary')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 rt-mb-20 col-md-6">
                                        <x-forms.label name="{{ __('salary_type') }}" :required="true"
                                            class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="rt-selectactive form-control @error('salary_type') is-invalid @enderror "
                                            name="salary_type">
                                            @foreach ($salary_types as $type)
                                                <option {{ $job->salary_type_id == $type->id ? 'selected' : '' }}
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
                            <div class="post-job-item rt-mb-15">
                                <h4 class="f-size-18 ft-wt-5 rt-mb-20 lh-1">{{ __('advance_information') }}</h4>
                                <div class="row">
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="education" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="select2-taggable form-control @error('education') is-invalid @enderror "
                                            name="education">
                                            @foreach ($educations as $education)
                                                <option {{ $job->education_id == $education->id ? 'selected' : '' }}
                                                    value="{{ $education->id }}">
                                                    {{ $education->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('education')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <input type="checkbox" id="education_limit" name="education_limit"
                                            value="1" {{ $job->education_limit == 1 ? 'checked' : '' }}>
                                        Limit applications based on the selected value

                                    </div>
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="experience" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="select2-taggable form-control @error('experience') is-invalid @enderror "
                                            name="experience">
                                            @foreach ($experiences as $experience)
                                                <option {{ $job->experience_id == $experience->id ? 'selected' : '' }}
                                                    value="{{ $experience->id }}">
                                                    {{ $experience->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('experience')
                                            <span class="error invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <input type="checkbox" id="experience_limit" name="experience_limit"
                                            value="1" {{ $job->experience_limit == 1 ? 'checked' : '' }}>
                                        Limit applications based on the selected value

                                    </div>
                                    <div class="col-lg-4 col-md-6 rt-mb-20">
                                        <x-forms.label name="job_type" :required="true" class="tw-text-sm tw-mb-2" />
                                        <select
                                            class="rt-selectactive form-control @error('job_type') is-invalid @enderror "
                                            name="job_type">
                                            @foreach ($job_types as $job_type)
                                                <option {{ $job->job_type_id == $job_type->id ? 'selected' : '' }}
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
                                        <select name="min_age" class="form-control select2bs4 " id="min_age">
                                            @if ($job->min_age)
                                                {
                                                <option value="{{ $job->min_age }}" selected>{{ $job->min_age }}</option>

                                                }
                                            @endif
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
                                            class="form-control select2bs4 @error('max_age') is-invalid @enderror"
                                            id="max_age">
                                            @if ($job->max_age)
                                                {
                                                <option value="{{ $job->max_age }}" selected>{{ $job->max_age }}</option>

                                                }
                                            @endif

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

                                        <input type="checkbox" id="age_limit" name="age_limit" value="1"
                                            {{ $job->age_limit == 1 ? 'checked' : '' }}>
                                        Limit applications based on the selected value

                                    </div>
                                    <div class="col-md-6">
                                        <x-forms.label name="gender" for="gender" />
                                        <select name="gender" class="form-control select2bs4" id="gender">
                                            <option value="" selected disabled>Select Gender </option>
                                            <option value="male" @if ($job->gender == 'male') selected @endif>Male
                                            </option>
                                            <option value="female" @if ($job->gender == 'female') selected @endif>
                                                Female </option>
                                            <option value="both" @if ($job->gender == 'both') selected @endif>Both
                                            </option>

                                        </select>
                                        @error('gender')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ __($message) }}</strong>
                                            </span>
                                        @enderror
                                        <input type="checkbox" id="gender_limit" name="gender_limit" value="1"
                                            {{ $job->gender_limit == 1 ? 'checked' : '' }}>
                                        <label for="gender_limit">Limit applications based on the selected value</label>
                                    </div>


                                    <div class="col-lg-6 col-md-6 rt-mb-20">
                                        <x-forms.label name="vacancies" :required="true" class="tw-text-sm tw-mb-2" />
                                        <input value="{{ $job->vacancies }}" name="vacancies" type="text"
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
                                                <input value="{{ date('d-m-Y', strtotime($job->deadline)) }}"
                                                    name="deadline"
                                                    class="form-control !tw-ps-[55px] @error('deadline') is-invalid @enderror"
                                                    type="text" id="date" placeholder="d/m/y">
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
                                    </div>
                                </div>

                            </div>
                            <div class="row">
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
                                                            placeholder="{{ __('enter_city_name') }}" class="full-width"
                                                            value="{{ $job->exact_location ? $job->exact_location : $job->full_address }}" />
                                                        <br>
                                                        <div id="leaflet-map"></div>
                                                    </div>
                                                    @error('location')
                                                        <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-12 mt-4 custom-checkbox-wrap">
                                                    <label class="main tw-text-sm"
                                                        for="remoteWork">{{ __('fully_remote_position') }}- <span
                                                            class="tw-font-medium">{{ __('worldwide') }}</span>
                                                        <input type="checkbox" name="is_remote" id="remoteWork"
                                                            value="1" {{ $job->is_remote ? 'checked' : '' }}>
                                                        <span class="custom-checkbox"></span>
                                                    </label>
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
                                                        $agency_country = $job->country;
                                                        $agency_exact_location = $job->exact_location;
                                                    @endphp
                                                    <div class="card-footer row mt-4 border-0">
                                                        <span>
                                                            <img src="{{ asset('frontend/assets/images/loader.gif') }}"
                                                                alt="loading" width="50px" height="50px"
                                                                class="loader_position d-none">
                                                        </span>
                                                        <div class="location_secion">
                                                            {{ __('country') }}: <span
                                                                class="location_country">{{ $agency_country ?: $session_country }}</span>
                                                            </br>
                                                            {{ __('full_address') }}: <span
                                                                class="location_full_address">{{ $agency_exact_location ?: $session_exact_location }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        session([
                                            'selectedCountryId' => null,
                                            'selectedStateId' => null,
                                            'selectedCityId' => null,
                                        ]);
                                        session([
                                            'selectedCountryId' => $job->country,
                                            'selectedStateId' => $job->region,
                                            'selectedCityId' => $job->district,
                                        ]);
                                    @endphp
                                    <x-forms.label name="location" :required="true" class="tw-text-sm tw-mb-2" />
                                    <div class="card-body pt-0 row">
                                        <div class="col-12">
                                            @livewire('country-state-city', ['row' => true])

                                            @error('location')
                                                <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                            @enderror
                                            <input type="checkbox" id="city_limit" name="city_limit" value="1"
                                                {{ $job->city_limit == 1 ? 'checked' : '' }}>
                                            Limit applications based on the selected value
                                        </div>

                                    </div>
                                @endif
                            </div>
                            <div class="post-job-item rt-mb-32">
                                <h4 class="f-size-18 ft-wt-5 rt-mb-20 lh-1">{{ __('benefits') }}</h4>
                                <div class="benefits-tags">
                                    @foreach ($benefits as $benefit)
                                        <label for="benefit_{{ $benefit->id }}">
                                            <input
                                                @foreach ($job->benefits as $job_benefit)
                                            {{ $job_benefit->id == $benefit->id ? 'checked' : '' }} @endforeach
                                                type="checkbox" id="benefit_{{ $benefit->id }}" name="benefits[]"
                                                value="{{ $benefit->id }}">
                                            <span>{{ $benefit->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('benefits')
                                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-12 mb-4">
                                <div class="form-group">
                                    <x-forms.label name="skills" :required="false" />
                                    <select id="skills" name="skills[]"
                                        class="cw-ms-select form-control @error('skills') is-invalid @enderror" multiple
                                        data-cw-lookup="skills" data-cw-tags="1" data-placeholder="Search or add skills…">
                                        @foreach ($skills as $skill)
                                            <option
                                                @foreach ($job->skills as $job_skill)
                                                {{ $job_skill->id == $skill->id ? 'selected' : '' }} @endforeach
                                                value="{{ $skill->id }}">{{ $skill->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('skills')
                                        <span class="invalid-feedback" role="alert">{{ __($message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3" id="dynamic-inputs">

                                @foreach ($dynamicInputs as $index => $input)
                                    @php
                                        $inputData = $inputsData->firstWhere('agency_attribute_id', $input->id); // Match translation with attribute
                                        // dd($inputData);
                                    @endphp

                                    <div class="form-group">
                                        <label for="dynamic_inputs_{{ $input->id }}">
                                            {{ ucwords(str_replace('_', ' ', $input->attribute_name)) }}
                                        </label>
                                        <input type="text" name="dynamic_inputs[{{ $index }}][value]"
                                            class="form-control" value="{{ $inputData->attribute_value ?? '' }}"
                                            placeholder="{{ ucwords(str_replace('_', ' ', $input->attribute_name)) }}">

                                        @error('dynamic_inputs.' . $index . '.value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <input type="hidden" name="dynamic_inputs[{{ $index }}][id]"
                                            value="{{ $input->id }}">
                                    </div>
                                @endforeach

                            </div>
                            <div class="post-job-item rt-mb-32">
                                <h4 class="f-size-18 ft-wt-5 tw-mb-3 lh-1">{{ __('job_description') }}</h4>
                                <div class="col-md-12">
                                    <textarea id="image_ckeditor" class="form-control @error('description') is-invalid @enderror" name="description">{{ $job->description }}</textarea>
                                    @error('description')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Arabic / Bilingual Fields --}}
                            <div class="post-job-item rt-mb-15">
                                <h4 class="f-size-18 ft-wt-5 tw-mb-3 lh-1">
                                    العنوان بالعربية
                                    <small class="text-muted fw-normal" style="font-size:13px">(Arabic Job Title — Optional)</small>
                                </h4>
                                <div class="col-md-12">
                                    <input type="text"
                                           name="title_ar"
                                           value="{{ old('title_ar', $job->title_ar) }}"
                                           class="form-control @error('title_ar') is-invalid @enderror"
                                           placeholder="مثال: مهندس برمجيات"
                                           dir="rtl"
                                           style="text-align:right">
                                    @error('title_ar')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="post-job-item rt-mb-32">
                                <h4 class="f-size-18 ft-wt-5 tw-mb-3 lh-1">
                                    وصف الوظيفة بالعربية
                                    <small class="text-muted fw-normal" style="font-size:13px">(Arabic Description — Optional)</small>
                                </h4>
                                <div class="col-md-12">
                                    <textarea name="description_ar"
                                              class="form-control @error('description_ar') is-invalid @enderror"
                                              rows="6"
                                              dir="rtl"
                                              style="text-align:right; font-family:inherit"
                                              placeholder="اكتب وصف الوظيفة باللغة العربية هنا...">{{ old('description_ar', $job->description_ar) }}</textarea>
                                    @error('description_ar')
                                        <span class="error invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Additional Questions --}}
                            @if (currentAgency()->question_feature_enable)
                                <div x-data="appQuestion()" x-init="select2Alpine"
                                    class="post-job-item rt-mb-15 tw-w-full tw-overflow-hidden ">
                                    <h4 class="f-size-18 ft-wt-5 rt-mb-20 lh-1">{{ __('add_screening_questions') }}</h4>

                                    <div class="row">
                                        <div class=" rt-mb-20">
                                            <div class="col-lg-12">
                                                <div x-show="isAddingNewQuestion" class="tw-flex justify-content-between">
                                                    <label class="tw-text-sm tw-mb-2 mb-2" for="for">
                                                        {{ __('create_new_screening_question') }}
                                                    </label>
                                                    <a x-show="isAddingNewQuestion" href="#"
                                                        @click.prevent="isAddingNewQuestion = false">{{ __('choose_from_existing_question') }}</a>
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
                                                    class="form-control " type="text" placeholder="Add Question ">
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
                                            data-placeholder="Select Questions" name="agencyQuestions[]"
                                            class="select2 form-control">

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
                            @endif

                            <div class="row tw-mb-8">
                                <div class="col-12">
                                    <div class="applied-job-on">
                                        <div class="row">
                                            <h2>{{ __('apply_job_on') }}:</h2>
                                            <!-- apply_on -->
                                            <div id="applied_on_app"
                                                class="radio-check col-lg-4 d-flex {{ $job->apply_on === 'app' ? 'checked' : '' }}"
                                                onclick="RadioChecked('app')">
                                                <input type="radio" {{ $job->apply_on === 'app' ? 'checked' : '' }}
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
                                                class="radio-check col-lg-4 d-flex {{ $job->apply_on === 'custom_url' ? 'checked' : '' }}"
                                                onclick="RadioChecked('custom_url')">
                                                <input type="radio"
                                                    {{ $job->apply_on === 'custom_url' ? 'checked' : '' }}
                                                    name="apply_on" value="custom_url" id="app-custom_url">
                                                <label for="app-custom_url">
                                                    <h4 class="d-inline-block">{{ __('external_platform') }}</h4>
                                                    <p class="tw-mb-0">
                                                        {{ __('candidate_apply_job_on_your_website_all_application_on_your_own_website') }}.
                                                    </p>
                                                </label>
                                            </div>
                                            <div id="applied_on_email"
                                                class="radio-check col-lg-4 d-flex {{ $job->apply_on === 'email' ? 'checked' : '' }}"
                                                onclick="RadioChecked('email')">
                                                <input type="radio" {{ $job->apply_on === 'email' ? 'checked' : '' }}
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
                                                        <input value="{{ $job->apply_url }}" name="apply_url"
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
                                                        <input value="{{ $job->apply_email }}" name="apply_email"
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
                            <div class="post-job-item rt-mb-15">
                                <button type="submit" class="btn btn-primary rt-mr-10">
                                    <span class="button-content-wrapper ">
                                        <span class="button-icon align-icon-right">
                                            <i class="ph-arrow-right"></i>
                                        </span>
                                        <span class="button-text">
                                            {{ __('post_job') }}
                                        </span>
                                    </span>
                                </button>
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

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }

        .mymap {
            border-radius: 12px;
        }

        .mt-n-11 {
            margin-top: -11px;
        }

        .custom-checkbox-wrap .main input:checked~.custom-checkbox:after {
            left: 8% !important;
        }
    </style>
    <style>
.card {
    transition: all 0.3s ease;
}
.col-lg-980 {
    width: 100%;
}

.card:hover {
    transform: translateY(-3px);
}

.dashboard-account-setting-item {
    background: #ffffff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.dashboard-account-setting-item h6 {
    font-weight: 600;
    margin-bottom: 20px;
}

.btn-primary {
    border-radius: 10px;
    padding: 8px 24px;
    font-weight: 500;
}

.form-control,
.select2-container--default .select2-selection--single {
    border-radius: 10px !important;
    min-height: 44px;
}

.mborder {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 8px 12px;
}

.add-new-social {
    border-radius: 10px;
    font-weight: 500;
}
</style>
@endsection

@section('frontend_scripts')
<script>
    function toggleCustomInput(select) {
        const customInput = document.getElementById('custom_product');
        if (select.value === 'custom') {
            customInput.style.display = 'block';
            customInput.value = ''; // Clear the custom input if 'Custom Option' is selected
        } else {
            customInput.style.display = 'none';
            customInput.value = ''; // Clear the custom input if another option is selected
        }
    }
</script>
    <script>
        .create(document.querySelector('#image_ckeditor'))
            .then(editor => {
                editor.model.document.on('change:data', () => {
                    const content = editor.getData();

                    // Regular expression to match numbers and email addresses
                    const invalidPattern = /\b\d+\b|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

                    if (invalidPattern.test(content)) {
                        // Remove invalid content
                        const sanitizedContent = content.replace(invalidPattern, '');
                        editor.setData(sanitizedContent);

                        // Optionally, display a warning message
                        alert("Numbers or email addresses are not allowed in the description.");
                    }
                });
            })
            .catch(error => {
                console.error('Error initializing CKEditor:', error);
            });
    </script>
    @livewireScripts
    <script>window.cwSettingsLookupUrl = @json(url('/agency/job-form/lookup'));</script>
    <script src="{{ asset('js/candidate-settings-select2.js') }}?v={{ @filemtime(public_path('js/candidate-settings-select2.js')) ?: '1' }}"></script>
    <script>
        $(document).ready(function() {
            $('.select21').select2();
        });
        window.addEventListener('render-select2', event => {
            $('.select21').select2();
        })
    </script>

    <script>
        $(document).ready(function() {
            $('.select2-question').select2();
        });
        window.addEventListener('render-select2', event => {
            $('.select2-question').select2();
        })
    </script>
    @stack('js')
    <script src="{{ asset('backend/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script defer src="{{ asset('backend/js/alpine.min.js') }}"></script>
    <script>
        function appQuestion() {
            return {
                allQuestions: @json($questions),
                selectedQuestions: @json($job->questions),
                selectedQuestionsIds: @json($job->questions->pluck('id')->toArray()),
                newQuestion: '',
                isAddingNewQuestion: false,
                newQuestionSave: true,
                isRequired: false,
                addQuestion: function() {

                    if (!this.newQuestion) return;


                    axios.post('/agency/questions', {
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

            this.select2.val(this.selectedQuestionsIds);

            this.select2.trigger('change');
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
                var items = [];
                this.allQuestions.forEach((item) => {
                    if (values.includes(item.id.toString())) {
                        items.push(item);
                    }

                });
                this.selectedQuestions = items;
            });
        }
    </script>


    @if (app()->getLocale() == 'ar')
        <script defer src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js"></script>
    @endif

    @if (config('templatecookie.map_show'))
        @php $activeMap = $map ?? ($setting->default_map ?? 'leaflet'); @endphp
        @if ($activeMap === 'leaflet')
            @include('map::set-edit-leafletmap', ['lat' => $job->lat, 'long' => $job->long])
        @elseif ($activeMap === 'google-map' && filled($setting->google_map_key))
            @include('map::set-edit-googlemap', ['job' => $job])
        @endif
    @endif

    <script>
        var start_day = '{{ $start_day }}'
        var end_day = '{{ $end_day }}'

        //init datepicker
        $("#date").attr("autocomplete", "off");
        //init datepicker
        $('#date').off('focus').datepicker({
            format: 'dd-mm-yyyy',
            startDate: `${end_day}d`,
            endDate: `+${end_day}d`,
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
            // endDate: `+${max_days}d`
        }).on('click',
            function() {
                $(this).datepicker('show');
            }
        );
    </script>

    <script>
        var salary_mode = "{!! old('salary_mode', $job->salary_mode) !!}";

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
    </script>
@endsection
