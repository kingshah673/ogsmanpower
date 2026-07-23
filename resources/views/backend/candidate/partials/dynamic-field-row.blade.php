<div class="row mb-3" id="dynamic-input-{{ $attribute->id }}">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge badge-secondary mb-1">{{ $sectionLabel ?? $attribute->section }}</span>
                        <label class="font-weight-bold text-primary d-block mb-1">{{ $attribute->attribute_name }}</label>
                        <small class="text-muted">{{ ucfirst($attribute->input_type) }}</small>
                    </div>
                    <div class="col-md-4 text-md-right mt-2 mt-md-0">
                        <button class="btn btn-danger btn-sm" onclick="deleteInput({{ $attribute->id }})">
                            <i class="fas fa-trash"></i> {{ __('Delete') }}
                        </button>
                        <div class="form-check form-switch d-inline-block ml-2">
                            <input class="form-check-input" type="checkbox" onchange="toggleActive({{ $attribute->id }}, this.checked ? 1 : 0)" {{ $attribute->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small">{{ $attribute->is_active ? __('Active') : __('Inactive') }}</label>
                        </div>
                        <div class="form-check form-switch d-inline-block ml-2">
                            <input class="form-check-input" type="checkbox" onchange="toggleRequired({{ $attribute->id }}, this.checked ? 1 : 0)" {{ $attribute->is_required ? 'checked' : '' }}>
                            <label class="form-check-label small">{{ $attribute->is_required ? __('Required') : __('Optional') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
