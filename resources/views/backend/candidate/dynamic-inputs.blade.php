@extends('backend.layouts.app')
@section('title', __('Dynamic Inputs'))

@section('content')
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <style>
        .form-check-input {
            width: 40px;
            /* Adjust width */
            height: 20px;
            /* Adjust height */
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #28a745;
            /* Change to green when checked */
        }

        .form-check-label {
            margin-left: 10px;
            /* Space between switch and label */
            font-weight: bold;
            /* Make label bold */
        }

        /* Optional: Smooth transition effect */
        .form-check-input {
            transition: background-color 0.3s ease;
        }
    </style>
    @if (auth()->user()->hasRole('superadmin') || userCan('candidate.update') || userCan('candidate.create'))
        <div class="container-fluid">
            <!-- Button to add a new dynamic input -->
            <div class="row mb-3">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openDynamicInputModal()">Add Dynamic
                        Input</button>
                </div>
            </div>

            <!-- Dynamic Inputs Display -->
            <div class="container-fluid" id="dynamic-inputs-list">
                @foreach ($candidate->attributes as $attribute)
                    <div class="row mb-4" id="dynamic-input-{{ $attribute->id }}">
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <!-- Input Field -->
                                        <div class="col-md-8 col-sm-12">
                                            <div class="form-group mb-0">
                                                <label
                                                    class="font-weight-bold text-primary">{{ $attribute->attribute_name }}</label>
                                                <input type="{{ $attribute->input_type }}"
                                                    name="dynamic_inputs[{{ $attribute->attribute_name }}]"
                                                    class="form-control form-control-lg"
                                                    value="{{ $attribute->attribute_value }}"
                                                    placeholder="{{ $attribute->attribute_name }}" disabled>
                                            </div>
                                        </div>

                                        <!-- Buttons Parallel to Input (Responsive) -->
                                        <div class="col-md-4 col-sm-12 text-md-right text-center mt-2 mt-md-0"
                                            style="margin-top: 30px !important;">
                                            <div class="btn-group" role="group">
                                                {{-- <button class="btn btn-warning btn-sm" onclick="editInput({{ $attribute->id }})">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button> --}}
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="deleteInput({{ $attribute->id }})">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>

                                                <!-- Active/Inactive Toggle Switch -->
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="activeSwitch{{ $attribute->id }}"
                                                        onchange="toggleActive({{ $attribute->id }}, this.checked ? 1 : 0)"
                                                        {{ $attribute->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="activeSwitch{{ $attribute->id }}">
                                                        <span
                                                            class="switch-label">{{ $attribute->is_active ? 'Active' : 'Inactive' }}</span>
                                                    </label>
                                                </div>

                                                <!-- Required/Optional Toggle Switch -->
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="requiredSwitch{{ $attribute->id }}"
                                                        onchange="toggleRequired({{ $attribute->id }}, this.checked ? 1 : 0)"
                                                        {{ $attribute->is_required ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="requiredSwitch{{ $attribute->id }}">
                                                        <span
                                                            class="switch-label">{{ $attribute->is_required ? 'Required' : 'Optional' }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Add FontAwesome for icons -->

        <!-- Optional: Add your JavaScript functions for editInput, deleteInput, etc. -->

    @endif

    <!-- Add Dynamic Input Modal -->
    <div class="modal fade" id="dynamicInputModal" tabindex="-1" aria-labelledby="dynamicInputModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dynamicInputModalLabel">Add Dynamic Input</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="inputLabel">Input Label</label>
                        <input type="text" id="inputLabel" class="form-control" placeholder="Enter input label">
                    </div>
                    <div class="form-group">
                        <label for="inputType">Input Type</label>
                        <select id="inputType" class="form-control" onchange="toggleOptionsField(this.value)">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="date">Date</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="file">File Upload</option>
                        </select>
                    </div>
                    <div class="form-group" id="dropdownOptionsGroup" style="display:none">
                        <label for="inputOptions">Dropdown Options <small class="text-muted">(one per line)</small></label>
                        <textarea id="inputOptions" class="form-control" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="inputRequired">Is Required?</label>
                        <select id="inputRequired" class="form-control">
                            <option value="1">Required</option>
                            <option value="0">Optional</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputActive">Is Active?</label>
                        <select id="inputActive" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    {{-- <button type="button" class="btn btn-primary" onclick="addDynamicInput()">Add Input</button> --}}
                    <button type="button" class="btn btn-primary" onclick="addDynamicInput({{ $candidate->id }})">Add
                        Input</button>

                </div>
            </div>
        </div>
    </div>

    <script>
        function openDynamicInputModal() {
            $('#dynamicInputModal').modal('show');
        }

        function toggleOptionsField(type) {
            document.getElementById('dropdownOptionsGroup').style.display = (type === 'dropdown') ? 'block' : 'none';
        }

        function addDynamicInput() {
            var inputLabel    = document.getElementById("inputLabel").value;
            var inputType     = document.getElementById("inputType").value;
            var inputRequired = document.getElementById("inputRequired").value;
            var inputActive   = document.getElementById("inputActive").value;
            var inputOptions  = document.getElementById("inputOptions").value;

            if (inputLabel && inputType) {
                $.ajax({
                    url: "{{ route('candidate.add_dynamic_input') }}",
                    method: "POST",
                    data: {
                        label: inputLabel,
                        type: inputType,
                        required: inputRequired,
                        active: inputActive,
                        options: inputOptions,
                        candidate_id: {{ $candidate->id }},
                        _token: "{{ csrf_token() }}"
                    },
                    beforeSend: function() {
                        // Optional: Show a loading indicator
                        console.log("Sending request...");
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create the new HTML element for the dynamic input
                            var newInputHtml = `
            <div class="row mb-4" id="dynamic-input-${response.attribute.id}">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8 col-sm-12">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold text-primary">${response.attribute.attribute_name}</label>
                                        <input type="${response.attribute.input_type}"
                                               name="dynamic_inputs[${response.attribute.attribute_name}]"
                                               class="form-control form-control-lg"
                                               placeholder="${response.attribute.attribute_name}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-12 text-md-right text-center mt-2 mt-md-0" style="margin-top: 30px !important;">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-danger btn-sm" onclick="deleteInput(${response.attribute.id})">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="activeSwitch${response.attribute.id}"
                                                   onchange="toggleActive(${response.attribute.id}, this.checked ? 1 : 0)"
                                                   ${response.attribute.is_active ? 'checked' : ''}>
                                            <label class="form-check-label" for="activeSwitch${response.attribute.id}">
                                                <span class="switch-label">${response.attribute.is_active ? 'Active' : 'Inactive'}</span>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requiredSwitch${response.attribute.id}"
                                                   onchange="toggleRequired(${response.attribute.id}, this.checked ? 1 : 0)"
                                                   ${response.attribute.is_required ? 'checked' : ''}>
                                            <label class="form-check-label" for="requiredSwitch${response.attribute.id}">
                                                <span class="switch-label">${response.attribute.is_required ? 'Required' : 'Optional'}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                            // Append the new input HTML to the dynamic input list
                            $('#dynamic-inputs-list').append(newInputHtml);
                            $('#dynamicInputModal').modal('hide'); // Hide the modal
                        } else {
                            alert('Failed to add input: ' + response.message);
                        }
                    },

                    error: function(xhr) {
                        console.error(xhr); // Log error response for debugging
                        alert('Failed to add input: ' + xhr.responseText);
                    }
                });
            } else {
                alert("Input label and type are required.");
            }
        }


        function editInput(id) {
            // Logic for editing the input type, required/optional, and active/inactive status
        }

        function deleteInput(id) {
            if (confirm("Are you sure you want to delete this input?")) {
                $.ajax({
                    url: "{{ route('candidate.delete_dynamic_input', ['id' => 0]) }}".replace('/0', '/' + id),
                    method: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#dynamic-input-' + id).remove();
                        }
                    },
                    error: function() {
                        alert('Failed to delete input');
                    }
                });
            }
        }

        function toggleActive(id, isActive) {
            $.ajax({
                url: "{{ route('candidate.toggle_active') }}",
                method: "POST",
                data: {
                    id: id,
                    is_active: isActive,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        // Update the label text accordingly
                        $('#activeSwitch' + id).siblings('label').text(isActive ? 'Active' : 'Inactive');
                    } else {
                        alert('Failed to update status.');
                    }
                },
                error: function() {
                    alert('Failed to update status.');
                }
            });
        }

        function toggleRequired(id, isRequired) {
            $.ajax({
                url: "{{ route('candidate.toggle_required') }}",
                method: "POST",
                data: {
                    id: id,
                    is_required: isRequired,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        // Update the label text accordingly
                        $('#requiredSwitch' + id).siblings('label').text(isRequired ? 'Required' : 'Optional');
                    } else {
                        alert('Failed to update status.');
                    }
                },
                error: function() {
                    alert('Failed to update status.');
                }
            });
        }
    </script>
@endsection
