@php
    $type = $type ?? 'seeker';
    $portal = $type === 'employer' ? 'employer' : 'seeker';
    $builtinMap = $portal === 'employer'
        ? \App\Services\DynamicFieldService::employerBuiltinFields()
        : \App\Services\DynamicFieldService::seekerBuiltinFields();
@endphp

<div class="dynamic-fields-sections-panel">
    @foreach ($sections as $sectionKey => $sectionLabel)
        @php
            $sectionFields = $groupedSections[$sectionKey] ?? collect();
            $builtinFields = $builtinMap[$sectionKey] ?? [];
        @endphp
        <div class="card mb-3 shadow-sm section-card" data-section="{{ $sectionKey }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ $sectionLabel }}</h5>
                    <small class="text-muted">{{ $sectionKey }}</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary"
                    onclick="openDynamicInputModal('{{ $sectionKey }}')">
                    <i class="fas fa-plus"></i> {{ __('Add custom field') }}
                </button>
            </div>
            <div class="card-body">
                @if (count($builtinFields))
                    <h6 class="text-muted text-uppercase small mb-2">{{ __('Built-in fields') }}</h6>
                    @foreach ($builtinFields as $field)
                        @include('backend.partials.builtin-field-row', ['field' => $field])
                    @endforeach
                @endif

                @if ($sectionFields->isNotEmpty())
                    <h6 class="text-muted text-uppercase small mb-2 {{ count($builtinFields) ? 'mt-3' : '' }}">
                        {{ __('Custom fields') }}
                    </h6>
                    @foreach ($sectionFields as $attribute)
                        @include('backend.partials.dynamic-field-row', [
                            'attribute' => $attribute,
                            'type' => $type,
                        ])
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
</div>
