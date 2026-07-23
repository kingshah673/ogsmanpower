@props([
    'name' => 'phone',
    'id' => null,
    'value' => '',
    'placeholder' => null,
    'optional' => false,
    'errorTarget' => null,
    'fullPhoneTarget' => null,
    'initialCountry' => null,
    'hint' => null,
    'invalidMessage' => null,
    'class' => 'form-control',
])

@php
    $inputId = $id ?: $name;
    $countryIso = strtolower($initialCountry ?: default_phone_country_iso());
@endphp

<div {{ $attributes->merge(['class' => 'fromGroup rt-mb-15']) }}>
    <input
        type="tel"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ old($name, $value) }}"
        @class([$class, 'intl-phone-input', 'is-invalid' => $errors->has($name)])
        placeholder="{{ $placeholder ?? __('phone_number') }}"
        data-initial-country="{{ $countryIso }}"
        @if($optional) data-phone-optional="true" @endif
        @if($errorTarget) data-error-target="{{ $errorTarget }}" @endif
        @if($fullPhoneTarget) data-full-phone-target="{{ $fullPhoneTarget }}" @endif
        @if($hint) data-phone-hint="{{ $hint }}" @endif
        data-invalid-message="{{ $invalidMessage ?? 'Please enter a valid phone number or leave it empty.' }}"
        autocomplete="tel"
    >
    @if($errorTarget)
        <small id="{{ $errorTarget }}" class="intl-phone-error text-danger"></small>
    @endif
    @error($name)
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>
