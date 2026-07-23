@props([
    'fields' => collect(),
    'namePrefix' => 'dynamic_inputs',
])

@if ($fields->isNotEmpty())
    @foreach ($fields as $index => $input)
        @php
            $fieldId = $input->definition_id ?? $input->id;
            $label = ucwords(str_replace('_', ' ', $input->attribute_name));
            $options = \App\Services\DynamicFieldService::parseDropdownOptions($input->options ?? null);
            $required = !empty($input->is_required);
            $inputName = $namePrefix . '[' . $index . ']';
        @endphp
        <div class="col-lg-6 mb-3 dynamic-field-item">
            <label class="pointer body-font-4 d-block text-gray-900 rt-mb-8" for="df_{{ $fieldId }}">
                {{ $label }}@if ($required)<span class="text-danger">*</span>@endif
            </label>

            @if (($input->input_type ?? 'text') === 'textarea')
                <textarea id="df_{{ $fieldId }}" name="{{ $inputName }}[value]" class="form-control"
                    rows="3" placeholder="{{ $label }}" @if ($required) required @endif>{{ old($namePrefix . '.' . $index . '.value', $input->attribute_value ?? '') }}</textarea>
            @elseif (($input->input_type ?? '') === 'date')
                <input type="date" id="df_{{ $fieldId }}" name="{{ $inputName }}[value]" class="form-control"
                    value="{{ old($namePrefix . '.' . $index . '.value', $input->attribute_value ?? '') }}"
                    @if ($required) required @endif>
            @elseif (($input->input_type ?? '') === 'dropdown' && count($options))
                <select id="df_{{ $fieldId }}" name="{{ $inputName }}[value]" class="form-control"
                    @if ($required) required @endif>
                    <option value="">{{ __('select_one') }}</option>
                    @foreach ($options as $opt)
                        <option value="{{ $opt }}" @selected(old($namePrefix . '.' . $index . '.value', $input->attribute_value ?? '') == $opt)>
                            {{ $opt }}
                        </option>
                    @endforeach
                </select>
            @elseif (($input->input_type ?? '') === 'file')
                <input type="file" id="df_{{ $fieldId }}" name="{{ $inputName }}[file]" class="form-control"
                    @if ($required && empty($input->attribute_value)) required @endif>
                @if (!empty($input->attribute_value))
                    <small class="text-muted d-block mt-1">
                        {{ __('current_file') }}:
                        <a href="{{ asset($input->attribute_value) }}" target="_blank" rel="noopener">{{ basename($input->attribute_value) }}</a>
                    </small>
                @endif
            @else
                <input type="{{ in_array($input->input_type ?? 'text', ['email', 'number', 'password'], true) ? $input->input_type : 'text' }}"
                    id="df_{{ $fieldId }}" name="{{ $inputName }}[value]" class="form-control"
                    value="{{ old($namePrefix . '.' . $index . '.value', $input->attribute_value ?? '') }}"
                    placeholder="{{ $label }}" @if ($required) required @endif>
            @endif

            <input type="hidden" name="{{ $inputName }}[definition_id]" value="{{ $fieldId }}">
            <input type="hidden" name="{{ $inputName }}[is_required]" value="{{ $required ? 1 : 0 }}">
        </div>
    @endforeach
@endif
