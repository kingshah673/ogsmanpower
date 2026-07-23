@extends('components.website.company.layout.app')

@section('title')
    {{ __('Applicant CV') }}
@endsection

@section('main')
@php
    $knownCodes = collect($resumeLanguages ?? bilingualResumeLanguages())->pluck('code')->all();
    $selectedLang = old('language_code', $candidate->language_code ?: 'en');
    $isCustomLang = $selectedLang && ! in_array($selectedLang, $knownCodes, true);
    $defaultFormat = old('format', $candidate->resume_format ?: 'general_format');
    $applicantName = $candidate->user->name ?? 'Applicant';
@endphp

<div class="dashboard-wrapper seeker-settings-page">
<div class="container">
<div class="dashboard-right">

<x-website.company.employer-page-header
    :title="__('Bilingual CV') . ' — ' . $applicantName"
    :subtitle="__('Choose a format and language, then view or download your professional CV.')"
>
    <x-slot:actions>
        <a href="{{ route('company.application.detail', [$candidate->id, $appliedJob->job_id]) }}" class="pv-topbar-btn">
            <i class="fas fa-eye"></i> {{ __('Application') }}
        </a>
        <a href="{{ route('company.applicants') }}" class="pv-topbar-btn">
            <i class="fas fa-users"></i> {{ __('Applicants') }}
        </a>
    </x-slot:actions>
</x-website.company.employer-page-header>

<div class="glass-card"><div class="glass-card-body">

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@if($appliedJob->job)
    <p class="small text-muted mb-3">Applied to: <strong>{{ $appliedJob->job->title }}</strong></p>
@endif

@if($uploadedResume)
<div class="pro-card mb-4">
    <div class="pro-title mb-2">Uploaded application resume</div>
    <p class="small text-muted mb-3">
        This applicant uploaded
        @if($uploadedResume->name)
            <strong>{{ $uploadedResume->name }}</strong>
        @else
            a resume file
        @endif
        with their application.
    </p>
    <a href="{{ route('website.candidate.download.cv', $uploadedResume) }}" class="pro-btn d-inline-block text-decoration-none">
        Download uploaded resume
    </a>
</div>
@else
<div class="alert alert-warning">
    No resume file was uploaded with this application. Generate a profile CV below (including bilingual).
</div>
@endif

{{-- ================= LANGUAGE + CV FORM ================= --}}
<div class="pro-card mb-4">

<form action="{{ route('company.view.applicant.resume') }}" method="POST" enctype="multipart/form-data" target="_blank">
@csrf
<input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
<input type="hidden" name="job_id" value="{{ $appliedJob->job_id }}">

<div class="mb-4">
    <div class="pro-title mb-2">{{ __('Bilingual Resume Language') }}</div>
    <p class="small text-muted mb-2">
        {{ __('Choose any supported language for the translated column. English stays on the left.') }}
    </p>

    <select class="form-control pro-select @error('language_code') is-invalid @enderror"
        name="language_code" id="bilingual_language_code">
        <option value="" disabled {{ ! $selectedLang ? 'selected' : '' }}>{{ __('Select language') }}</option>
        @foreach(($resumeLanguages ?? bilingualResumeLanguages()) as $lang)
            <option value="{{ $lang['code'] }}"
                {{ $selectedLang === $lang['code'] ? 'selected' : '' }}>
                {{ $lang['name'] }}
            </option>
        @endforeach
        <option value="custom" {{ $isCustomLang ? 'selected' : '' }}>{{ __('Other language…') }}</option>
    </select>

    <div class="mt-2" id="bilingual_custom_lang_wrap" style="{{ $isCustomLang ? '' : 'display:none;' }}">
        <label class="small text-muted mb-1" for="language_code_custom">{{ __('Language code (e.g. tr, ur, bn)') }}</label>
        <input type="text" class="form-control" name="language_code_custom" id="language_code_custom"
            maxlength="10" placeholder="tr"
            value="{{ old('language_code_custom', $isCustomLang ? $selectedLang : '') }}">
        <small class="text-muted">{{ __('Use ISO language codes. AI translation supports most world languages.') }}</small>
    </div>
</div>

{{-- ================= FORMAT ================= --}}
<div class="pro-title text-center mb-3">Choose Your CV Format</div>

<label class="pro-option">
    <input type="radio" name="format" value="general_format"
        {{ $defaultFormat == 'general_format' ? 'checked' : '' }}>
    General Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="driver_format"
        {{ $defaultFormat == 'driver_format' ? 'checked' : '' }}>
    Driver Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="guard_format"
        {{ $defaultFormat == 'guard_format' ? 'checked' : '' }}>
    Security Guard Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="beautician_format"
        {{ $defaultFormat == 'beautician_format' ? 'checked' : '' }}>
    Beautician Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="web_developer_format"
        {{ $defaultFormat == 'web_developer_format' ? 'checked' : '' }}>
    Professional Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="bike_rider_format"
        {{ $defaultFormat == 'bike_rider_format' ? 'checked' : '' }}>
    Bike Rider Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="bilangual_format"
        {{ $defaultFormat == 'bilangual_format' ? 'checked' : '' }}>
    Bilingual Format
    @if(!empty(config('services.openai.key')))
        <small class="d-block text-muted mt-1">AI-powered translation included when no paid subscription exists.</small>
    @endif
</label>

<input type="hidden" name="action_type" id="action_type1" value="view">

<div class="mt-4">
    <button type="submit" class="pro-btn w-100 mb-2"
        onclick="document.getElementById('action_type1').value='view'">
        View Resume
    </button>

    <button type="submit" class="pro-btn w-100"
        onclick="document.getElementById('action_type1').value='download'">
        Download Resume
    </button>
</div>

</form>

</div>

</div></div>

</div>
</div>
</div>

@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('bilingual_language_code');
        var wrap = document.getElementById('bilingual_custom_lang_wrap');
        if (!select || !wrap) return;

        function toggleCustomLang() {
            wrap.style.display = select.value === 'custom' ? '' : 'none';
        }

        select.addEventListener('change', toggleCustomLang);
        toggleCustomLang();
    });
</script>
@endsection
