@extends('backend.layouts.app')
@section('title', __('Seeker Settings — Dynamic Fields'))

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">{{ __('Seeker dynamic fields') }}</h4>
                    <p class="text-muted mb-0 small">
                        {{ __('Define extra fields for') }}
                        <a href="{{ url('/candidate/settings') }}" target="_blank" rel="noopener">/candidate/settings</a>.
                        {{ __('Each settings section is listed below — add fields where they should appear.') }}
                    </p>
                </div>
                @if (auth()->user()->hasRole('superadmin') || userCan('candidate.update') || userCan('candidate.create'))
                    <button type="button" class="btn btn-primary btn-sm" onclick="openDynamicInputModal()">
                        {{ __('Add field') }}
                    </button>
                @endif
            </div>
        </div>

        @if (auth()->user()->hasRole('superadmin') || userCan('candidate.update') || userCan('candidate.create'))
            <div id="dynamic-inputs-list">
                @include('backend.partials.dynamic-fields-sections-panel', [
                    'sections' => $sections,
                    'groupedSections' => $groupedSections,
                    'type' => 'seeker',
                ])
            </div>
        @endif
    </div>

    <div class="modal fade" id="dynamicInputModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add dynamic field') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="inputSection">{{ __('Settings section') }}</label>
                        <select id="inputSection" class="form-control">
                            @foreach ($sections as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputLabel">{{ __('Field label') }}</label>
                        <input type="text" id="inputLabel" class="form-control" placeholder="{{ __('e.g. Mother Name') }}">
                    </div>
                    <div class="form-group">
                        <label for="inputType">{{ __('Input type') }}</label>
                        <select id="inputType" class="form-control" onchange="toggleOptionsField(this.value)">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="date">Date</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="file">File</option>
                            <option value="email">Email</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                    <div class="form-group" id="dropdownOptionsGroup" style="display:none">
                        <label for="inputOptions">{{ __('Dropdown options') }} <small class="text-muted">({{ __('one per line') }})</small></label>
                        <textarea id="inputOptions" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="inputRequired">{{ __('Required?') }}</label>
                        <select id="inputRequired" class="form-control">
                            <option value="1">{{ __('Required') }}</option>
                            <option value="0">{{ __('Optional') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputActive">{{ __('Active?') }}</label>
                        <select id="inputActive" class="form-control">
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" onclick="addDynamicInput()">{{ __('Add field') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDynamicInputModal(sectionKey) {
            if (sectionKey) {
                document.getElementById('inputSection').value = sectionKey;
            }
            $('#dynamicInputModal').modal('show');
        }

        function toggleOptionsField(type) {
            document.getElementById('dropdownOptionsGroup').style.display = (type === 'dropdown') ? 'block' : 'none';
        }

        function addDynamicInput() {
            var label = document.getElementById('inputLabel').value;
            var type = document.getElementById('inputType').value;
            if (!label || !type) { alert('{{ __('Label and type are required.') }}'); return; }

            $.ajax({
                url: "{{ route('candidate.add_dynamic_input') }}",
                method: 'POST',
                data: {
                    label: label,
                    type: type,
                    section: document.getElementById('inputSection').value,
                    required: document.getElementById('inputRequired').value,
                    active: document.getElementById('inputActive').value,
                    options: document.getElementById('inputOptions').value,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) { location.reload(); }
                    else { alert(response.message || 'Failed'); }
                },
                error: function(xhr) { alert('Failed: ' + (xhr.responseJSON?.message || xhr.statusText)); }
            });
        }

        function deleteInput(id) {
            if (!confirm('{{ __('Delete this field?') }}')) return;
            $.ajax({
                url: "{{ route('candidate.delete_dynamic_input', ['id' => 0]) }}".replace('/0', '/' + id),
                method: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function(r) { if (r.success) location.reload(); }
            });
        }

        function toggleActive(id, isActive) {
            $.post("{{ route('candidate.toggle_active') }}", { id: id, is_active: isActive, _token: "{{ csrf_token() }}" });
        }

        function toggleRequired(id, isRequired) {
            $.post("{{ route('candidate.toggle_required') }}", { id: id, is_required: isRequired, _token: "{{ csrf_token() }}" });
        }
    </script>
@endsection
