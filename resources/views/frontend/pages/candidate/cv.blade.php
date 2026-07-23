@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('Bilingual CV') }}
@endsection

@section('main')

<div class="dashboard-wrapper seeker-settings-page">
<div class="container">
<div class="dashboard-right">

<x-website.candidate.seeker-page-header
    :title="__('Bilingual CV')"
    :subtitle="__('Choose a format and language, then view or download your professional CV.')"
>
    <x-slot:actions>
        <a href="{{ route('candidate.profile.view') }}" class="pv-topbar-btn">
            <i class="fas fa-eye"></i> {{ __('View Profile') }}
        </a>
    </x-slot:actions>
</x-website.candidate.seeker-page-header>

<div class="glass-card"><div class="glass-card-body">

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

{{-- ================= LANGUAGE + CV FORM ================= --}}
<div class="pro-card mb-4">

<form action="{{ route('candidate.viewResume') }}" method="POST" enctype="multipart/form-data" target="_blank">
@csrf

<div class="mb-4">
    <div class="pro-title mb-2">{{ __('Bilingual Resume Language') }}</div>
    <p class="small text-muted mb-2">
        {{ __('Choose any supported language for the translated column. English stays on the left.') }}
    </p>

    @php
        $knownCodes = collect($resumeLanguages ?? bilingualResumeLanguages())->pluck('code')->all();
        $selectedLang = old('language_code', $candidate->language_code ?: 'en');
        $isCustomLang = $selectedLang && ! in_array($selectedLang, $knownCodes, true);
    @endphp

    <select class="form-control pro-select @error('language_code') is-invalid @enderror"
        name="language_code" id="bilingual_language_code">
        <option value="" disabled {{ ! $selectedLang ? 'selected' : '' }}>{{ __('Select language') }}</option>
        @foreach(($resumeLanguages ?? bilingualResumeLanguages()) as $lang)
            <option value="{{ $lang['code'] }}"
                {{ $selectedLang === $lang['code'] ? 'selected' : '' }}>
                {{ $lang['name'] }}
                {{ $subscription->contains('language_code', $lang['code']) ? '(' . __('Paid') . ')' : '' }}
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
        {{ $candidate->resume_format == 'general_format' ? 'checked' : '' }}>
    General Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="driver_format"
        {{ $candidate->resume_format == 'driver_format' ? 'checked' : '' }}>
    Driver Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="guard_format"
        {{ $candidate->resume_format == 'guard_format' ? 'checked' : '' }}>
    Security Guard Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="beautician_format"
        {{ $candidate->resume_format == 'beautician_format' ? 'checked' : '' }}>
    Beautician Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="web_developer_format"
        {{ $candidate->resume_format == 'web_developer_format' ? 'checked' : '' }}>
    Professional Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="bike_rider_format"
        {{ $candidate->resume_format == 'bike_rider_format' ? 'checked' : '' }}>
    Bike Rider Format
</label>

<label class="pro-option">
    <input type="radio" name="format" value="bilangual_format"
        {{ $candidate->resume_format == 'bilangual_format' ? 'checked' : '' }}>
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

{{-- ================= PAYMENTS ================= --}}
<div class="pro-card mt-3">

<div class="pro-title mb-3">Payments</div>

@php
$languages = collect($resumeLanguages ?? bilingualResumeLanguages())
    ->pluck('name', 'code')
    ->all();
@endphp

<table class="table pro-table">

<thead>
<tr>
<th>Language</th>
<th>Payment Method</th>
<th>Status</th>
</tr>
</thead>

<tbody>
@foreach ($subscription as $sub)
<tr>
<td>{{ $languages[$sub->language_code] ?? $sub->language_code }}</td>
<td>{{ $sub->payment_method }}</td>
<td>{{ $sub->status }}</td>
</tr>
@endforeach
</tbody>

</table>

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