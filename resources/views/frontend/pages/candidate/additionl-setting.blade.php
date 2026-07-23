{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('profile') }}
@endsection
@section('main')
    <div class="dashboard-wrapper seeker-settings-page">
        <div class="container">
            <div class="dashboard-right">

                <x-website.candidate.seeker-page-header
                    :title="__('Additional Information')"
                    :subtitle="__('Complete custom profile fields for your profession.')"
                />

                <div class="glass-card"><div class="glass-card-body">
                                {{-- Dynamic Custom Fields --}}
                                @if(isset($dynamicInputs) && $dynamicInputs->count())
                                <div class="card tw-mb-4">
                                    <div class="card-body">
                                        <h3 class="f-size-18 lh-1 mb-3">{{ __('Additional Information') }}</h3>
                                        <form id="dynamicAttributesForm">
                                            @csrf
                                            <div class="row">
                                                @foreach($dynamicInputs as $attr)
                                                <div class="col-lg-6 rt-mb-24">
                                                    <label class="f-size-14 text-gray-700 rt-mb-8">
                                                        {{ $attr->attribute_name }}
                                                        @if($attr->is_required)<span class="text-danger">*</span>@endif
                                                    </label>

                                                    @if($attr->input_type === 'textarea')
                                                        <textarea
                                                            name="values[{{ $attr->id }}]"
                                                            class="form-control"
                                                            rows="3"
                                                            {{ $attr->is_required ? 'required' : '' }}
                                                        >{{ $attr->attribute_value }}</textarea>

                                                    @elseif($attr->input_type === 'date')
                                                        <input
                                                            type="text"
                                                            name="values[{{ $attr->id }}]"
                                                            class="form-control flatpickr-date"
                                                            value="{{ $attr->attribute_value }}"
                                                            placeholder="YYYY-MM-DD"
                                                            autocomplete="off"
                                                            {{ $attr->is_required ? 'required' : '' }}
                                                        >

                                                    @elseif($attr->input_type === 'dropdown')
                                                        @php
                                                            $opts = json_decode($attr->options ?? '[]', true) ?? [];
                                                        @endphp
                                                        <select name="values[{{ $attr->id }}]" class="form-control" {{ $attr->is_required ? 'required' : '' }}>
                                                            <option value="">-- Select --</option>
                                                            @foreach($opts as $opt)
                                                                <option value="{{ $opt }}" {{ $attr->attribute_value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                            @endforeach
                                                        </select>

                                                    @elseif($attr->input_type === 'file')
                                                        @if($attr->attribute_value)
                                                            <div class="mb-1">
                                                                <a href="{{ asset('storage/' . $attr->attribute_value) }}" target="_blank" class="text-primary small">View current file</a>
                                                            </div>
                                                        @endif
                                                        <input
                                                            type="file"
                                                            name="values[{{ $attr->id }}]"
                                                            class="form-control"
                                                            {{ $attr->is_required && !$attr->attribute_value ? 'required' : '' }}
                                                        >

                                                    @else
                                                        <input
                                                            type="text"
                                                            name="values[{{ $attr->id }}]"
                                                            class="form-control"
                                                            value="{{ $attr->attribute_value }}"
                                                            placeholder="{{ $attr->attribute_name }}"
                                                            {{ $attr->is_required ? 'required' : '' }}
                                                        >
                                                    @endif
                                                </div>
                                                @endforeach
                                            </div>
                                            <button type="button" id="saveDynamicFields" class="btn btn-primary mt-2">
                                                {{ __('Save') }}
                                            </button>
                                            <span id="dynamicSaveMsg" class="ms-2 text-success small d-none">Saved!</span>
                                        </form>
                                    </div>
                                </div>
                                @endif

                                {{-- Basic Setting  --}}

                                {{-- Change Email --}}

                                <div class="card tw-mb-4">
                                    <div class="card-body">
                                        <div class="dashboard-account-setting-item setting-border">
                                            <div class="tw-flex  lg:tw-mt-0 tw-items-center tw-justify-between">
                                                <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                    {{ __('Change Email') }}</h3>

                                                <button type="button" id="emailToggleForm"
                                                    class="btn btn-icon tw-ml-4 ">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <circle cx="12" cy="12" r="10" stroke="#007BFF"
                                                            stroke-width="2" />
                                                        <path d="M12 7v10M7 12h10" stroke="#007BFF" stroke-width="2" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <form id="emailForm" class="tw-hidden"
                                                action="{{ route('candidate.settingUpdate') }}" method="POST">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="type" value="account">
                                                <div class="dashboard-account-setting-item">
                                                    <div class="row tw-mb-8">
                                                        <div class="col-lg-6 mt-2">
                                                            <x-forms.label :required="true" name="email"
                                                                class="f-size-14 text-gray-700 rt-mb-8" />
                                                            <div class="fromGroup rt-mb-15">
                                                                <input name="account_email"
                                                                    value="{{ auth()->user()->email }}"
                                                                    class="form-control @error('account_email') is-invalid @enderror"
                                                                    id="account_email" type="email"
                                                                    placeholder="{{ __('email_address') }}" required>

                                                            </div>
                                                            @error('account_email')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        @if (session('requested_email'))
                                                            <small> Your email address
                                                                {{ session('requested_email') }} is
                                                                unverified . Check you email </small>
                                                        @endif


                                                    </div>
                                                    <button type="submit" class="btn btn-primary">
                                                        {{ __('update_email') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Change Password --}}

                                <div class="card tw-mb-4">
                                    <div class="card-body">
                                        <div class="dashboard-account-setting-item setting-border">

                                            <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">

                                                <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                    {{ __('change_password') }}
                                                </h3>

                                                <button type="button" id="passwordToggleForm"
                                                    class="btn btn-icon tw-ml-4 ">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <circle cx="12" cy="12" r="10" stroke="#007BFF"
                                                            stroke-width="2" />
                                                        <path d="M12 7v10M7 12h10" stroke="#007BFF" stroke-width="2" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <form id="passwordForm" class="tw-hidden"
                                                action="{{ route('candidate.settingUpdate') }}" method="POST">
                                                @csrf
                                                @method('put')
                                                <input type="hidden" name="type" value="password">
                                                <div class="row">
                                                    <div class="col-lg-6 rt-mb-32">
                                                        <x-forms.label :required="true" name="new_password"
                                                            class="f-size-14 text-gray-700 rt-mb-6" />
                                                        <div class="fromGroup rt-mb-15">
                                                            <div class="d-flex">
                                                                <input name="password"
                                                                    class="form-control @error('password') is-invalid @enderror"
                                                                    id="password-hide_show" type="password"
                                                                    placeholder="{{ __('password') }}" required>
                                                                <div
                                                                    class="has-badge @error('password') has-badge-cutom @enderror">
                                                                    <i
                                                                        class="ph-eye @error('password') m-3 @enderror"></i>
                                                                </div>
                                                            </div>
                                                            @error('password')
                                                                <span role="alert"
                                                                    class="text-danger">{{ __($message) }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 rt-mb-32">
                                                        <x-forms.label :required="true" name="confirm_password"
                                                            class="f-size-14 text-gray-700 rt-mb-6" />
                                                        <div class="fromGroup rt-mb-15">
                                                            <input name="password_confirmation"
                                                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                                                id="password-hide_show1" type="password"
                                                                placeholder="{{ __('confirm_password') }}" required>
                                                            <div
                                                                class="has-badge @error('password') has-badge-cutom @enderror select-icon__one">
                                                                <i class="ph-eye"></i>
                                                            </div>
                                                            @error('password_confirmation')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <button type="submit" class="btn btn-primary">
                                                            {{ __('save_changes') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Delete Account --}}

                                <div class="card tw-mb-4">
                                    <div class="card-body">
                                        <div class="dashboard-account-setting-item setting-border">
                                            <div class="row">
                                                <div
                                                    class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">

                                                    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">
                                                        {{ __('close_delete_account') }}</h3>

                                                    <button type="button" id="deleteAccountToggleForm"
                                                        class="btn btn-icon tw-ml-4 ">
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <circle cx="12" cy="12" r="10" stroke="#007BFF"
                                                                stroke-width="2" />
                                                            <path d="M12 7v10M7 12h10" stroke="#007BFF"
                                                                stroke-width="2" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div id="deleteAccountForm" class="col-lg-6 tw-hidden">
                                                    <p>{{ __('account_delete_msg') }}</p>
                                                    <form action="{{ route('candidate.settingUpdate') }}"
                                                        id="AccountDelete" method="POST">
                                                        @csrf
                                                        @method('put')
                                                        <input type="hidden" name="type" value="account-delete">
                                                        <button type="button" onclick="AccountDelete()"
                                                            class="btn p-0 text-danger-500">
                                                            <span class="button-content-wrapper ">
                                                                <span class="button-icon">
                                                                    <i class="ph-x-circle"></i>
                                                                </span>
                                                                <span class="button-text">
                                                                    {{ __('close_account') }}
                                                                </span>
                                                            </span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>





                            </div>
                        </div></div>

            </div>
        </div>
    </div>

@endsection

@section('frontend_links')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <!-- >=>Leaflet Map<=< -->
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }

        .w-100-percent {
            width: 100% !important;
        }

        #jobrole #basic-addon1 {
            width: 50px !important;
            margin-left: 28px !important;
        }

        .border-cutom {
            border-radius: 5px 0 0 5px !important;
        }

        .input-group-text-custom {
            max-height: 48px;
            padding: 12px;
            background-color: #e9ecef;
            border-radius: 0 5px 5px 0;
        }

        .has-badge-cutom {
            top: 34% !important;
        }

        .mymap {
            border-radius: 12px;
            z-index: 999;
        }

        @media (max-width: 768px) {
            .btn {
                line-height: 18px !important;
                padding: 10px 10px 10px !important;
                border-radius: 4px !important;
                font-size: 12px !important;

            }
        }

        .slider {
            width: 100%;
            margin: 20px 0;
        }

        .salary-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
    </style>
@endsection

@section('frontend_scripts')
    @livewireScripts
    <script>
        document.getElementById('emailToggleForm').addEventListener('click', function() {
            const form = document.getElementById('emailForm');


            form.classList.toggle('tw-hidden');

        });
        document.getElementById('passwordToggleForm').addEventListener('click', function() {
            const form = document.getElementById('passwordForm');


            form.classList.toggle('tw-hidden');

        });
        document.getElementById('deleteAccountToggleForm').addEventListener('click', function() {
            const form = document.getElementById('deleteAccountForm');

            form.classList.toggle('tw-hidden');

        });

    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>



    <script>
        // Function to display image preview for a given input and image element
        function previewImage(input, imageElementId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(imageElementId).src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Add event listeners for image preview
        document.getElementById('passportImageInput').addEventListener('change', function() {
            previewImage(this, 'passportImagePreview');
        });

        document.getElementById('licenseImageInput').addEventListener('change', function() {
            previewImage(this, 'licenseImagePreview');
        });
    </script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select21').select2();
        });
        window.addEventListener('render-select2', event => {
            console.log('fired');
            $('.select21').select2();
        })
    </script>
    @stack('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Flatpickr for dynamic date fields
        document.querySelectorAll('.flatpickr-date').forEach(function(el) {
            flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
        });

        // AJAX save for dynamic attribute fields
        document.getElementById('saveDynamicFields')?.addEventListener('click', function() {
            var form   = document.getElementById('dynamicAttributesForm');
            var data   = new FormData(form);
            var btn    = this;
            var msg    = document.getElementById('dynamicSaveMsg');

            btn.disabled = true;

            fetch("{{ route('candidate.saveAttributeValues') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        || '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: data,
            })
            .then(r => r.json())
            .then(function(res) {
                btn.disabled = false;
                if (res.success) {
                    msg.classList.remove('d-none');
                    setTimeout(() => msg.classList.add('d-none'), 3000);
                } else {
                    alert(res.message ?? 'Save failed.');
                }
            })
            .catch(function() {
                btn.disabled = false;
                alert('Network error — please try again.');
            });
        });
    </script>
    <script src="{{ asset('backend/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    @if (app()->getLocale() == 'ar')
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                                                                                                                                                                                                                                                                                                                                                                                                                                            ">
        </script>
    @endif
    <script>
        //init datepicker
        $("#available_id_date").attr("autocomplete", "off");

        availableStatus('{{ old('status', $candidate->status) }}');

        $('#available_status').on('change', function() {
            availableStatus(this.value);
        });

        function availableStatus(status) {
            if (status == 'available_in') {
                $('#available_in_status').removeClass('d-none');
            } else {
                $('#available_in_status').addClass('d-none');
            }
        }


        function UploadMode(param) {
            if (param === 'photo') {
                $('#photo-uploadMode').removeClass('d-none');
                $('#photo-oldMode').addClass('d-none');
            } else {
                $('#banner-uploadMode').removeClass('d-none');
                $('#banner-oldMode').addClass('d-none');
            }
        }
        //init datepicker
        $("#date").attr("autocomplete", "off");
        //init datepicker
        $('#date').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });

        $("#passportIssueDate").attr("autocomplete", "off");
        //init datepicker
        $('#passportIssueDate').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });
        $("#passportExpiryDate").attr("autocomplete", "off");
        //init datepicker
        $('#passportExpiryDate').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });
    </script>
    <script>
        $('#visibility').on('change', function() {
            $(this).submit();
        });
        $('#alert').on('change', function() {
            $(this).submit();
        });

        function AccountDelete() {
            if (confirm("Are you sure ??") == true) {
                $('#AccountDelete').submit();
            } else {
                return false;
            }
        }

        function resumeDelete() {
            if (confirm("Are you sure ?") == true) {
                $('#resumeForm').submit();
            } else {
                return false;
            }
        }

        function editResume(id, name, size) {
            $('#resume_id_input').val(id);
            $('#resume_name_input').val(name);
            $('#resume_file_size').html(size);
            $('#resumeEditModal').modal('show');
        }
        $('.cv-remove-image').on('click', function() {
            $('.resume-file-upload-input').replaceWith($('.resume-file-upload-input').clone());
            $('.resume-file-upload-content').hide();
            $('.cv-image-upload-wrap').show();
            $('.resume-file-upload-input').val('');
        })

        function resumeManageReadURL(input, type) {
            if (type == 'add') {
                var fileName = document.querySelector('#resume_add_input').files[0].name;
                var fileSize = document.querySelector('#resume_add_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_add_input').files[0].type;
            } else {
                var fileName = document.querySelector('#resume_edit_input').files[0].name;
                var fileSize = document.querySelector('#resume_edit_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_edit_input').files[0].type;
            }
            $('.resume_selected_file_name').html(fileName);
            $('.resume_selected_file_size').html(fileSize.toFixed(4));
            $('.resume_selected_file_type').html(fileType);
            if (input.files && input.files[0]) {
                console.log(input.className)
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (input.className === 'profile-file-upload-input') {
                        $('.profile-image-upload-wrap').hide();
                        $('.profile-file-upload-image').attr('src', e.target.result);
                        $('.profile-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'banner-file-upload-input') {
                        $('.banner-image-upload-wrap').hide();
                        $('.banner-file-upload-image').attr('src', e.target.result);
                        $('.banner-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'resume-file-upload-input') {
                        $('.cv-image-upload-wrap').hide();
                        $('.resume-file-upload-content.none').show();
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                $('.profile-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.profile-file-upload-input').replaceWith($('.profile-file-upload-input').clone());
                    $('.profile-file-upload-content').hide();
                    $('.profile-file-upload-image').attr('src', '');
                    $('.profile-image-upload-wrap').show();
                })
                $('.banner-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.banner-file-upload-input').replaceWith($('.banner-file-upload-input').clone());
                    $('.banner-file-upload-content').hide();
                    $('.banner-file-upload-image').attr('src', '');
                    $('.banner-image-upload-wrap').show();
                })
            }
        }
        setTimeout(function() {
            {{ session()->forget('type') }}
        }, 10000);
    </script>
    <script>
        @php
            $link1 = 'https://maps.googleapis.com/maps/api/js?key=';
            $link2 = $setting->google_map_key;
            $Link3 = '&callback=initMap&libraries=places,geometry';
            $scr = $link1 . $link2 . $Link3;
        @endphp;
    </script>




@endsection
