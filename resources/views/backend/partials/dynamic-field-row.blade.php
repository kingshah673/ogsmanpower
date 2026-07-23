@php
    $type = $type ?? 'seeker';
@endphp

<div class="row mb-3 dynamic-field-admin-row" id="dynamic-input-{{ $attribute->id }}">
    <div class="col-12">
        <div class="border rounded p-3 bg-light">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="font-weight-bold text-primary">{{ $attribute->attribute_name }}</div>
                    <small class="text-muted d-block">
                        {{ ucfirst($attribute->input_type ?? 'text') }}
                        · {{ $attribute->is_required ? __('Required') : __('Optional') }}
                        · {{ $attribute->is_active ? __('Active') : __('Inactive') }}
                    </small>
                </div>
                <div class="col-md-5 text-md-right mt-2 mt-md-0">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteInput({{ $attribute->id }})">
                        <i class="fas fa-trash"></i> {{ __('Delete') }}
                    </button>
                    <label class="ml-2 mb-0 small">
                        <input type="checkbox" onchange="toggleActive({{ $attribute->id }}, this.checked ? 1 : 0)"
                            {{ $attribute->is_active ? 'checked' : '' }}>
                        {{ __('Active') }}
                    </label>
                    <label class="ml-2 mb-0 small">
                        <input type="checkbox" onchange="toggleRequired({{ $attribute->id }}, this.checked ? 1 : 0)"
                            {{ $attribute->is_required ? 'checked' : '' }}>
                        {{ __('Required') }}
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
