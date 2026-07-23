@php
    $sectionFields = isset($dynamicFieldsBySection[$section])
        ? collect($dynamicFieldsBySection[$section])
        : collect();
@endphp
@if ($sectionFields->isNotEmpty())
    <div class="row dynamic-fields-row" data-section="{{ $section }}">
        <x-dynamic-form-fields :fields="$sectionFields" />
    </div>
@endif
