<div class="row mb-2 builtin-field-row">
    <div class="col-12">
        <div class="border rounded p-2 bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <div>
                    <span class="badge badge-info mb-1">{{ __('Built-in') }}</span>
                    <span class="font-weight-bold">{{ $field['label'] }}</span>
                </div>
                <small class="text-muted">
                    {{ ucfirst($field['type'] ?? 'text') }}
                    @if (!empty($field['required']))
                        · {{ __('Required') }}
                    @else
                        · {{ __('Optional') }}
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>
