{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.company.layout.app')

@section('title')
    {{ __('settings') }}
@endsection

@section('main')
<div class="dashboard-wrapper seeker-module-page">
    <div class="container">
        <div class="dashboard-right">

            <x-website.company.employer-page-header
                :title="__('Company Settings')"
                subtitle="Manage your company profile, branding, contact information and security settings."
            >
                <x-slot:actions>
                    <a href="{{ route('website.employe.details', auth()->user()->username) }}" class="pv-topbar-btn" target="_blank" rel="noopener">
                        <i class="fas fa-eye"></i> {{ __('View Public Profile') }}
                    </a>
                </x-slot:actions>
            </x-website.company.employer-page-header>

            <div class="glass-card">
            <div class="glass-card-body p-0">

                @if ($errors->any())
                    <div class="alert alert-danger m-3" role="alert">
                        <strong>{{ __('please_fix_the_following_errors') }}:</strong>
                        <ul class="tw-mb-0 tw-mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Mobile scrollable nav --}}
                <div class="d-lg-none mb-3 overflow-auto">
                    <div class="d-flex gap-2 pb-2 flex-nowrap settings-mobile-nav">
                        <a href="#section-branding" class="btn btn-sm btn-outline-primary flex-shrink-0">Branding</a>
                        <a href="#section-info"     class="btn btn-sm btn-outline-primary flex-shrink-0">Company Info</a>
                        <a href="#section-social"   class="btn btn-sm btn-outline-primary flex-shrink-0">Social Media</a>
                        <a href="#section-contact"  class="btn btn-sm btn-outline-primary flex-shrink-0">Contact</a>
                        <a href="#section-account"  class="btn btn-sm btn-outline-primary flex-shrink-0">Account</a>
                        <a href="#section-password" class="btn btn-sm btn-outline-primary flex-shrink-0">Password</a>
                    </div>
                </div>

                {{-- Two-column layout: sidebar + sections --}}
                <div class="row g-4">

                    {{-- Desktop sticky sidebar --}}
                    <div class="col-lg-3 d-none d-lg-block">
                        <div class="sticky-top" style="top: 80px;">
                            <div class="card border-0 shadow-sm rounded-3 p-2">
                                <nav class="company-settings-nav">
                                    <a href="#section-branding" class="settings-nav-link active">
                                        <i class="ph-image me-2"></i> Branding
                                    </a>
                                    <a href="#section-info" class="settings-nav-link">
                                        <i class="ph-buildings me-2"></i> Company Info
                                    </a>
                                    <a href="#section-social" class="settings-nav-link">
                                        <i class="ph-share-network me-2"></i> Social Media
                                    </a>
                                    <a href="#section-contact" class="settings-nav-link">
                                        <i class="ph-map-pin me-2"></i> Contact & Location
                                    </a>
                                    <a href="#section-account" class="settings-nav-link">
                                        <i class="ph-user-circle me-2"></i> Account
                                    </a>
                                    <a href="#section-password" class="settings-nav-link">
                                        <i class="ph-lock me-2"></i> Password
                                    </a>
                                    <a href="#section-delete" class="settings-nav-link text-danger">
                                        <i class="ph-trash me-2"></i> Delete Account
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>

                    {{-- Content column --}}
                    <div class="col-lg-9 col-12">

                        {{-- ========== SECTION: Branding ========== --}}
                        <div class="settings-section-card" id="section-branding">
                            <div class="settings-section-header">
                                <i class="ph-image"></i>
                                <h5>{{ __('logo_banner_image') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" value="personal" name="type">

                                    <div class="row">
                                        <x-website.company.photo-section :user="$user" />
                                        <x-website.company.banner-section :user="$user" />
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-lg-6 mb-3">
                                            <x-forms.label name="company_name" required="true"
                                                class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <div class="fromGroup">
                                                <div class="form-control-icon">
                                                    <x-forms.input type="text" name="name"
                                                        value="{{ $user->name }}" placeholder="name"
                                                        id="name" />
                                                    @error('name')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 mb-3">
                                            <x-forms.label :required="false" name="about_us"
                                                class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <p class="text-muted tw-text-sm tw-mb-2">{{ __('Shown on your public employer profile as the company description.') }}</p>
                                            <textarea class="form-control ckedit @error('about_us') is-invalid @enderror"
                                                name="about_us" id="image_ckeditor">{!! $user->company->bio !!}</textarea>
                                            @error('about_us')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'section-branding', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                        <div class="col-lg-12 mt-2">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Company Info ========== --}}
                        <div class="settings-section-card" id="section-info">
                            <div class="settings-section-header">
                                <i class="ph-buildings"></i>
                                <h5>{{ __('founding_info') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="type" value="profile">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <x-forms.label name="organization_type"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <select name="organization_type" class="select2-taggable w-100-p">
                                                @foreach ($organization_types as $organization_type)
                                                    <option
                                                        {{ $user->company->organization_type_id == $organization_type->id ? 'selected' : '' }}
                                                        value="{{ $organization_type->id }}">
                                                        {{ $organization_type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <x-forms.label name="industry_type"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <select name="industry_type" class="select2-taggable w-100-p">
                                                @foreach ($industry_types as $industry_type)
                                                    <option
                                                        {{ $user->company->industry_type_id == $industry_type->id ? 'selected' : '' }}
                                                        value="{{ $industry_type->id }}">
                                                        {{ $industry_type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <x-forms.label name="team_size"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8"
                                                :required="false" />
                                            <select name="team_size" class="rt-selectactive w-100-p">
                                                @foreach ($team_sizes as $team_size)
                                                    <option
                                                        {{ $user->company->team_size_id == $team_size->id ? 'selected' : '' }}
                                                        value="{{ $team_size->id }}">
                                                        {{ $team_size->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-6 col-md-6 mb-3">
                                            <x-forms.label name="year_of_establishment"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8"
                                                :required="false" />
                                            <div class="fromGroup">
                                                <div class="d-flex align-items-center form-control-icon date datepicker">
                                                    <input type="text" name="establishment_date"
                                                        value="{{ $user->company->establishment_date ? date('d-m-Y', strtotime($user->company->establishment_date)) : old('establishment_date') }}"
                                                        id="date" placeholder="m/d/y"
                                                        class="form-control border-cutom @error('establishment_date') is-invalid @enderror" />
                                                    <label for="date"
                                                        class="input-group-addon tw-cursor-pointer input-group-text-custom">
                                                        <x-svg.calendar-icon />
                                                    </label>
                                                </div>
                                                @error('establishment_date')
                                                    <span class="text-danger">{{ __($message) }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-6 mb-3">
                                            <x-forms.label name="website" :required="false"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <div class="fromGroup has-icon2">
                                                <div class="form-control-icon">
                                                    <x-forms.input type="text" name="website"
                                                        value="{{ $user->company->website }}"
                                                        placeholder="Website url..." class="" />
                                                    <div class="icon-badge-2">
                                                        <x-svg.link-icon />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 mb-3">
                                            <x-forms.label name="company_vision" :required="false"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <textarea name="vision" class="ckedit" id="image_ckeditor_2">{{ $user->company->vision }}</textarea>
                                        </div>
                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'section-info', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                        <div class="col-lg-12 mt-2">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Social Media ========== --}}
                        <div class="settings-section-card" id="section-social">
                            <div class="settings-section-header">
                                <i class="ph-share-network"></i>
                                <h5>{{ __('social_media_profile') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" value="social" name="type">
                                    <div class="row">
                                        @forelse($socials as $social)
                                            <div class="col-12 custom-select-padding">
                                                <div class="d-flex">
                                                    <div class="d-flex mborder">
                                                        <div class="position-relative">
                                                            <select
                                                                class="w-100-p border-0 rt-selectactive form-control"
                                                                name="social_media[]">
                                                                <option value="" class="d-none" disabled>
                                                                    {{ __('select_one') }}</option>
                                                                <option {{ $social->social_media == 'facebook'  ? 'selected' : '' }} value="facebook">{{ __('facebook') }}</option>
                                                                <option {{ $social->social_media == 'twitter'   ? 'selected' : '' }} value="twitter">{{ __('twitter') }}</option>
                                                                <option {{ $social->social_media == 'instagram' ? 'selected' : '' }} value="instagram">{{ __('instagram') }}</option>
                                                                <option {{ $social->social_media == 'youtube'   ? 'selected' : '' }} value="youtube">{{ __('youtube') }}</option>
                                                                <option {{ $social->social_media == 'linkedin'  ? 'selected' : '' }} value="linkedin">{{ __('linkedin') }}</option>
                                                                <option {{ $social->social_media == 'pinterest' ? 'selected' : '' }} value="pinterest">{{ __('pinterest') }}</option>
                                                                <option {{ $social->social_media == 'reddit'    ? 'selected' : '' }} value="reddit">{{ __('reddit') }}</option>
                                                                <option {{ $social->social_media == 'github'    ? 'selected' : '' }} value="github">{{ __('github') }}</option>
                                                                <option {{ $social->social_media == 'other'     ? 'selected' : '' }} value="other">{{ __('other') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="w-100">
                                                            <input class="border-0" type="url" name="url[]"
                                                                placeholder="{{ __('profile_link_url') }}..."
                                                                value="{{ $social->url }}">
                                                        </div>
                                                    </div>
                                                    <div class="tw-ms-2">
                                                        <button
                                                            class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center remove-social-item"
                                                            type="button">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#18191C" stroke-width="1.5" stroke-miterlimit="10" />
                                                                <path d="M15 9L9 15" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M15 15L9 9" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12 custom-select-padding">
                                                <div class="d-flex">
                                                    <div class="d-flex mborder">
                                                        <div class="position-relative">
                                                            <select
                                                                class="w-100-p border-0 rt-selectactive form-control"
                                                                name="social_media[]">
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
                                                            <input class="border-0" type="url" name="url[]"
                                                                placeholder="{{ __('profile_link_url') }}...">
                                                        </div>
                                                    </div>
                                                    <div class="tw-ms-2">
                                                        <button
                                                            class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center remove-social-item"
                                                            type="button">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#18191C" stroke-width="1.5" stroke-miterlimit="10" />
                                                                <path d="M15 9L9 15" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M15 15L9 9" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforelse
                                        <div id="multiple_feature_part2"></div>
                                        <div class="col-12 mt-2">
                                            <button class="btn tw-bg-[#F1F2F4] w-100 add-new-social"
                                                onclick="add_features_field()" type="button">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10 17.5C14.1421 17.5 17.5 14.1421 17.5 10C17.5 5.85786 14.1421 2.5 10 2.5C5.85786 2.5 2.5 5.85786 2.5 10C2.5 14.1421 5.85786 17.5 10 17.5Z" stroke="#18191C" stroke-width="1.5" stroke-miterlimit="10" />
                                                    <path d="M6.875 10H13.125" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M10 6.875V13.125" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <span>{{ __('add_new_social_link') }}</span>
                                            </button>
                                        </div>
                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'section-social', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Contact & Location ========== --}}
                        <div class="settings-section-card" id="section-contact">
                            <div class="settings-section-header">
                                <i class="ph-map-pin"></i>
                                <h5>{{ __('company_location') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="type" value="contact">

                                    <x-website.map.map-warning />

                                    @if (config('templatecookie.map_show'))
                                        <div class="row">
                                            <div id="google-map-div"
                                                class="{{ $setting->default_map == 'google-map' ? '' : 'd-none' }}">
                                                <input id="searchInput" class="mapClass" type="text"
                                                    placeholder="Enter a location">
                                                <div class="map mymap" id="google-map"></div>
                                            </div>
                                            <div class="{{ $setting->default_map == 'leaflet' ? '' : 'd-none' }}">
                                                <input type="text" autocomplete="off" id="leaflet_search"
                                                    placeholder="{{ __('enter_city_name') }}" class="full-width"
                                                    value="{{ $user->company->exact_location ? $user->company->exact_location : $user->company->full_address }}" />
                                                <br>
                                                <div id="leaflet-map"></div>
                                            </div>
                                            @error('location')
                                                <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        @php
                                            $session_location = session()->get('location');
                                            $session_country = $session_location && array_key_exists('country', $session_location) ? $session_location['country'] : '-';
                                            $session_exact_location = $session_location && array_key_exists('exact_location', $session_location) ? $session_location['exact_location'] : '-';
                                            $company_country = $user->company->country;
                                            $company_exact_location = $user->company->exact_location;
                                        @endphp
                                        <div class="card-footer row mt-4 border-0">
                                            <span>
                                                <img src="{{ asset('frontend/assets/images/loader.gif') }}"
                                                    alt="loading" width="50px" height="50px"
                                                    class="loader_position d-none">
                                            </span>
                                            <div class="location_secion">
                                                {{ __('country') }}: <span class="location_country">{{ $company_country ?: $session_country }}</span>
                                                <br>
                                                {{ __('full_address') }}: <span class="location_full_address">{{ $company_exact_location ?: $session_exact_location }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <x-website.location.country-state-city-select
                                            prefix="company_settings"
                                            :selected-country="old('country', $user->company->country)"
                                            :selected-state="old('state', $user->company->region)"
                                            :selected-city="old('district', $user->company->district)"
                                        />
                                    @endif

                                    <div class="mt-4 pt-3 border-top">
                                        <h6 class="mb-3">{{ __('company_contact_public') }}</h6>
                                        <div class="row mb-3">
                                            <div class="col-lg-6 col-md-6 mb-3">
                                                <x-forms.label :required="false" name="phone"
                                                    class="pointer tw-text-sm d-block text-gray-900 rt-mb-8" />
                                                <x-forms.input type="text" id="phone" name="phone"
                                                    value="{{ $contact->phone }}"
                                                    placeholder="{{ __('phone_number') }}" class="phonecode"
                                                    data-initial-country="{{ default_phone_country_iso() }}" />
                                            </div>
                                            <div class="col-lg-6 col-md-6 mb-3">
                                                <x-forms.label :required="false" name="email"
                                                    class="pointer tw-text-sm d-block text-gray-900 rt-mb-8" />
                                                <div class="fromGroup has-icon2">
                                                    <div class="form-control-icon">
                                                        <x-forms.input type="email" name="email"
                                                            value="{{ $contact->email }}"
                                                            placeholder="{{ __('email_address') }}"
                                                            class="" />
                                                        <div class="icon-badge-2">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M21 5.25L12 13.5L3 5.25" stroke="var(--primary-500)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M3 5.25H21V18C21 18.1989 20.921 18.3897 20.7803 18.5303C20.6397 18.671 20.4489 18.75 20.25 18.75H3.75C3.55109 18.75 3.36032 18.671 3.21967 18.5303C3.07902 18.3897 3 18.1989 3 18V5.25Z" stroke="var(--primary-500)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M10.3628 12L3.23047 18.538" stroke="var(--primary-500)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                <path d="M20.7692 18.5381L13.6367 12" stroke="var(--primary-500)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @include('frontend.partials.dynamic-fields-section', ['section' => 'section-contact', 'dynamicFieldsBySection' => $dynamicFieldsBySection ?? []])
                                        <button type="submit" class="btn btn-primary">
                                            {{ __('save_changes') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Account Settings ========== --}}
                        <div class="settings-section-card" id="section-account">
                            <div class="settings-section-header">
                                <i class="ph-user-circle"></i>
                                <h5>{{ __('change_account_user_name_and_email') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="type" value="account">
                                    <div class="row mb-3">
                                        <div class="col-lg-8 col-md-8 mb-3">
                                            <x-forms.label :required="false" name="username"
                                                class="pointer tw-text-sm d-block text-gray-900 rt-mb-8" />
                                            <x-forms.input type="text" id="username" name="username"
                                                value="{{ $user->username }}"
                                                placeholder="{{ __('username') }}" class="phonecode" />
                                            <span id="username_error"
                                                class="invalid-feedback d-none">{{ __('username_has_already_been_taken') }}</span>
                                            <p class="mt-2 mb-0"><b>{{ __('profile_link') }}: </b>
                                                <a href="{{ config('app.url') }}/employer/{{ $user->username }}" target="_blank">
                                                    {{ config('app.url') }}/employer/<span id="profile_username">{{ $user->username }}</span>
                                                </a>
                                            </p>
                                        </div>
                                        <div class="col-lg-4 col-md-4 mb-3">
                                            <x-forms.label :required="true" name="email"
                                                class="f-size-14 text-gray-700 rt-mb-8" />
                                            <div class="fromGroup rt-mb-15">
                                                <input name="account_email" value="{{ auth()->user()->email }}"
                                                    class="form-control @error('account_email') is-invalid @enderror"
                                                    id="account_email" type="email"
                                                    placeholder="{{ __('email_address') }}" required>
                                            </div>
                                            @if (session('requested_email'))
                                                <small>Your email address {{ session('requested_email') }} is unverified. Check your email.</small>
                                            @endif
                                            @error('account_email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('save_changes') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Password ========== --}}
                        <div class="settings-section-card" id="section-password">
                            <div class="settings-section-header">
                                <i class="ph-lock"></i>
                                <h5>{{ __('change_password') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <form action="{{ route('company.settingUpdateInformation') }}" method="POST">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="type" value="password">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-6 mb-3">
                                            <x-forms.label :required="true" name="new_password"
                                                class="f-size-14 text-gray-700 rt-mb-8" />
                                            <div class="d-flex fromGroup rt-mb-15">
                                                <input name="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    id="password-hide_show" type="password"
                                                    placeholder="{{ __('password') }}" required="">
                                                <div class="has-badge">
                                                    <i class="ph-eye @error('password') m-3 @enderror"></i>
                                                </div>
                                            </div>
                                            @error('password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-lg-6 col-md-6 mb-3">
                                            <x-forms.label :required="true" name="confirm_password"
                                                class="f-size-14 text-gray-700 rt-mb-8" />
                                            <div class="fromGroup rt-mb-15">
                                                <input name="password_confirmation"
                                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                                    id="password-hide_show1" type="password"
                                                    placeholder="{{ __('confirm_password') }}" required="">
                                                <div class="has-badge select-icon__one">
                                                    <i class="ph-eye"></i>
                                                </div>
                                            </div>
                                            @error('password_confirmation')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('save_changes') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ========== SECTION: Delete Account ========== --}}
                        <div class="settings-section-card border-danger" id="section-delete">
                            <div class="settings-section-header" style="background:#fff5f5;border-bottom-color:#fee2e2;">
                                <i class="ph-trash" style="color:#ef4444;"></i>
                                <h5 style="color:#ef4444;">{{ __('close') }}/{{ __('delete') }} {{ __('account') }}</h5>
                            </div>
                            <div class="settings-section-body">
                                <p class="text-muted mb-3">{{ __('account_delete_msg') }}</p>
                                <form action="{{ route('company.settingUpdateInformation') }}"
                                    id="AccountDelete" method="POST">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="type" value="account-delete">
                                    <button type="button" onclick="AccountDelete()"
                                        class="btn btn-outline-danger">
                                        <span class="button-content-wrapper">
                                            <span class="button-icon">
                                                <i class="ph-x-circle"></i>
                                            </span>
                                            <span class="button-text">
                                                {{ __('close_account') }}
                                            </span>
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>{{-- /col-lg-9 --}}
                </div>{{-- /row --}}

                <div class="dashboard-footer text-center body-font-4 text-gray-500 mt-4">
                    {{-- <x-website.footer-copyright /> --}}
                </div>

            </div>{{-- /glass-card-body --}}
            </div>{{-- /glass-card --}}

        </div>{{-- /dashboard-right --}}
    </div>{{-- /container --}}
</div>{{-- /dashboard-wrapper --}}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <!-- Leaflet Map -->
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    <style>
        /* ---- Section cards ---- */
        .settings-section-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .settings-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
            background: #fafafa;
        }
        .settings-section-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        .settings-section-header i {
            font-size: 20px;
            color: #3b82f6;
        }
        .settings-section-body {
            padding: 24px;
        }

        /* ---- Sidebar nav ---- */
        .company-settings-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .settings-nav-link {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-radius: 8px;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background .15s, color .15s;
        }
        .settings-nav-link:hover,
        .settings-nav-link.active {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .settings-nav-link.text-danger:hover {
            background: #fff1f2;
            color: #dc2626;
        }

        /* ---- Logo change overlay ---- */
        .logo-current-wrap {
            width: 120px;
            height: 120px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        .logo-current-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }
        .logo-change-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 1;
            transition: background .2s;
            font-size: 12px;
            gap: 4px;
            pointer-events: none;
        }
        .logo-current-wrap:hover .logo-change-overlay {
            background: rgba(15, 23, 42, 0.7);
        }
        .logo-change-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .logo-change-btn:hover {
            border-color: #0a65cc;
            color: #0a65cc;
            background: #eff6ff;
        }

        /* ---- Banner change overlay ---- */
        .banner-current-wrap {
            width: 100%;
            min-height: 120px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        .banner-current-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .banner-change-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            opacity: 1;
            transition: background .2s;
            gap: 8px;
            pointer-events: none;
        }
        .banner-current-wrap:hover .banner-change-overlay {
            background: rgba(15, 23, 42, 0.65);
        }
        .banner-change-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .banner-change-btn:hover {
            border-color: #0a65cc;
            color: #0a65cc;
            background: #eff6ff;
        }

        /* ---- Misc ---- */
        .mymap { border-radius: 12px; }
        .ck-editor__editable_inline { min-height: 350px; }
        .input-group-text-custom {
            max-height: 48px;
            padding: 12px;
            background-color: #e9ecef;
            border-radius: 0 5px 5px 0;
        }
        .has-badge-cutom { top: 34% !important; }
        .border-cutom { border-radius: 5px 0 0 5px !important; }
        .mborder {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 8px 12px;
        }
        .add-new-social {
            border-radius: 10px;
            font-weight: 500;
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
    </style>
@endsection

@section('script')
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select21').not('.cw-location-select').select2();
            if (typeof window.cwBootLocationCascade === 'function') {
                window.cwBootLocationCascade();
            }
        });
        window.addEventListener('render-select2', function () {
            $('.select21').not('.cw-location-select').select2();
            if (typeof window.cwBootLocationCascade === 'function') {
                window.cwBootLocationCascade();
            }
        });
    </script>
    @stack('js')

    {{-- Leaflet --}}
    @include('map::set-edit-leafletmap', ['lat' => $user->company->lat, 'long' => $user->company->long])

    <script>
        // Username availability check
        $('#username').keyup(function() {
            var username = $(this).val();
            if (username.length) {
                axios.get('/check/username/' + username, { params: { type: "company_username" } })
                    .then(function(response) {
                        var exists = response.data;
                        if (exists) {
                            $('#username').addClass('is-invalid');
                            $('#username_error').removeClass('d-none');
                            $('#username_submit_btn').attr('disabled', 'disabled');
                        } else {
                            $('#username').removeClass('is-invalid');
                            $('#username_error').addClass('d-none');
                            $('#username_submit_btn').removeAttr('disabled');
                        }
                        $('#profile_username').html(username);
                    });
            }
        });

        // Show upload area and auto-open file picker
        function UploadMode(param) {
            if (param === 'photo') {
                $('#photo-uploadMode').removeClass('d-none');
                $('#photo-oldMode').addClass('d-none');
                setTimeout(function() {
                    var input = document.querySelector('.profile-file-upload-input');
                    if (input) input.click();
                }, 50);
            } else {
                $('#banner-uploadMode').removeClass('d-none');
                $('#banner-oldMode').addClass('d-none');
                setTimeout(function() {
                    var input = document.querySelector('.banner-file-upload-input');
                    if (input) input.click();
                }, 50);
            }
        }

        // Datepicker
        $('#date').datepicker({
            format: "dd-mm-yyyy",
            autoclose: true
        });

        // Add new social link row
        function add_features_field() {
            $("#multiple_feature_part2").append(`
            <div class="col-12 custom-select-padding">
                <div class="d-flex tw-items-center">
                    <div class="d-flex mborder">
                        <div class="position-relative">
                            <select class="w-100-p border-0 rt-selectactive-2 form-control" name="social_media[]">
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
                            <input class="border-0" type="url" name="url[]" placeholder="{{ __('profile_link_url') }}...">
                        </div>
                    </div>
                    <div class="tw-ms-2">
                        <button class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center remove-social-item" type="button">
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
            $(".rt-selectactive-2").select2({});
        }

        // Remove social link row — class-based so all rows work
        $(document).on("click", ".remove-social-item", function() {
            $(this).closest('.col-12.custom-select-padding').remove();
        });

        $('#visibility').on('change', function() { $(this).submit(); });
        $('#alert').on('change', function() { $(this).submit(); });

        function AccountDelete() {
            if (confirm("{{ __('are_you_sure') }}") == true) {
                $('#AccountDelete').submit();
            } else {
                return false;
            }
        }

        setTimeout(function() {
            {{ session()->forget('type') }}
        }, 10000);

        var item = {!! $user->company !!};

        // Highlight active nav link on scroll
        var sections = document.querySelectorAll('[id^="section-"]');
        var navLinks = document.querySelectorAll('.settings-nav-link');
        window.addEventListener('scroll', function() {
            var scrollPos = window.scrollY + 120;
            sections.forEach(function(sec) {
                if (sec.offsetTop <= scrollPos && sec.offsetTop + sec.offsetHeight > scrollPos) {
                    navLinks.forEach(function(link) { link.classList.remove('active'); });
                    var active = document.querySelector('.settings-nav-link[href="#' + sec.id + '"]');
                    if (active) active.classList.add('active');
                }
            });
        });
    </script>

    @if ($setting->default_map == 'google-map')
        <x-website.map.google-map-check />
        <script>
            function initMap() {
                var token = "{{ $setting->google_map_key }}";
                var oldlat = parseFloat(item.lat);
                var oldlng = parseFloat(item.long);
                const map = new google.maps.Map(document.getElementById("google-map"), {
                    zoom: 7,
                    center: { lat: oldlat, lng: oldlng },
                });
                const beachMarker = new google.maps.Marker({
                    draggable: true,
                    position: { lat: oldlat, lng: oldlng },
                    map,
                });
                google.maps.event.addListener(map, 'click', function(event) {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');
                    pos = event.latLng;
                    beachMarker.setPosition(pos);
                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`)
                        .then((data) => {
                            if (data.data.error_message) {
                                toastr.error(data.data.error_message, 'Error!');
                            }
                            const total = data.data.results.length;
                            let amount = total > 1 ? total - 2 : '';
                            const result = data.data.results.slice(amount);
                            let country = '', region = '';
                            for (let i = 0; i < result.length; i++) {
                                if (result[i].types[0] == 'country') country = result[i].formatted_address;
                                if (result[i].types[0] == 'administrative_area_level_1') {
                                    region = result[i].formatted_address.split(',').shift();
                                }
                            }
                            var form = new FormData();
                            form.append('lat', lat); form.append('lng', lng);
                            form.append('country', country); form.append('region', region);
                            form.append('exact_location', data.data.results[0].formatted_address);
                            setLocationSession(form);
                            $('.location_country').text(country);
                            $('.location_full_address').text(data.data.results[0].formatted_address || 'No address found');
                            $('.loader_position').addClass('d-none');
                            $('.location_secion').removeClass('d-none');
                        });
                });
                google.maps.event.addListener(beachMarker, 'dragend', function() {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');
                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`)
                        .then((data) => {
                            if (data.data.error_message) {
                                toastr.error(data.data.error_message, 'Error!');
                            }
                            const total = data.data.results.length;
                            let amount = total > 1 ? total - 2 : '';
                            const result = data.data.results.slice(amount);
                            let country = '', region = '';
                            for (let i = 0; i < result.length; i++) {
                                if (result[i].types[0] == 'country') country = result[i].formatted_address;
                                if (result[i].types[0] == 'administrative_area_level_1') {
                                    region = result[i].formatted_address.split(' ').shift();
                                }
                            }
                            var form = new FormData();
                            form.append('lat', lat); form.append('lng', lng);
                            form.append('country', country); form.append('region', region);
                            form.append('exact_location', data.data.results[0].formatted_address);
                            setLocationSession(form);
                            $('.location_country').text(country);
                            $('.location_full_address').text(data.data.results[0].formatted_address || 'No address found');
                            $('.loader_position').addClass('d-none');
                            $('.location_secion').removeClass('d-none');
                        });
                });
                var input = document.getElementById('searchInput');
                map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
                let country_code = '{{ current_country_code() }}';
                var autocomplete = country_code
                    ? new google.maps.places.Autocomplete(input, { componentRestrictions: { country: country_code } })
                    : new google.maps.places.Autocomplete(input);
                autocomplete.bindTo('bounds', map);
                var infowindow = new google.maps.InfoWindow();
                var marker = new google.maps.Marker({ map: map, anchorPoint: new google.maps.Point(0, -29) });
                autocomplete.addListener('place_changed', function() {
                    infowindow.close();
                    marker.setVisible(false);
                    var place = autocomplete.getPlace();
                    if (place.geometry.viewport) { map.fitBounds(place.geometry.viewport); }
                    else { map.setCenter(place.geometry.location); map.setZoom(17); }
                });
            }
            window.initMap = initMap;
            @php
                $link1 = 'https://maps.googleapis.com/maps/api/js?key=';
                $link2 = $setting->google_map_key;
                $Link3 = '&callback=initMap&libraries=places,geometry';
                $scr = $link1 . $link2 . $Link3;
            @endphp;
        </script>
        <script src="{{ $scr }}" async defer></script>
    @endif
@endsection
