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
                    :title="__('Documents')"
                    :subtitle="__('Upload passport, CNIC, and other supporting documents.')"
                />

                        <div class="glass-card"><div class="glass-card-body">

                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                @if(session('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif

                                <form id="attachmentForm"
                                    action="{{ route('candidate.settingUpdate') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('put')
                                    <input type="hidden" name="type" value="documents">

                                    <div class="row g-4">

                                        {{-- Passport Image --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('Passport Image') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="passport_image" id="passportImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="passportImagePreview"
                                                            src="{{ isset($attachments) && $attachments->passport_image ? asset('storage/candidates/'.$attachments->passport_image) : asset('images/candidates/img1.jpg') }}"
                                                            alt="passport">
                                                    </div>
                                                    {{-- OCR Scan Button --}}
                                                    <div class="mt-3 d-flex gap-2">
                                                        <button type="button" id="ocrScanBtn"
                                                            class="btn btn-sm btn-outline-primary w-100"
                                                            onclick="triggerPassportOCR()">
                                                            🔍 {{ __('Scan Passport OCR') }}
                                                        </button>
                                                        @if(isset($attachments) && $attachments->passport_image)
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-info w-100"
                                                                onclick="scanExistingPassport('{{ asset('storage/candidates/'.$attachments->passport_image) }}')">
                                                                📄 {{ __('Re-scan Saved') }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div id="ocrStatus" class="mt-2 small text-muted" style="display:none;"></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- CNIC Front --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('CNIC Front') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="cnic_front" id="cnicFrontImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="cnicFrontPreview"
                                                            src="{{ isset($attachments) && $attachments->cnic_front ? asset('storage/candidates/'.$attachments->cnic_front) : asset('images/candidates/img1.jpg') }}"
                                                            alt="cnic-front">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- CNIC Back --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('CNIC Back') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="cnic_back" id="cnicBackImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="cnicBackPreview"
                                                            src="{{ isset($attachments) && $attachments->cnic_back ? asset('storage/candidates/'.$attachments->cnic_back) : asset('images/candidates/img1.jpg') }}"
                                                            alt="cnic-back">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Police Character Certificate --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('Police Character Certificate') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="police_character_certificate" id="pPCImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="pPCPreview"
                                                            src="{{ isset($attachments) && $attachments->police_character_certificate ? asset('storage/candidates/'.$attachments->police_character_certificate) : asset('images/candidates/img1.jpg') }}"
                                                            alt="police-cert">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Medical --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('Medical') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="medical" id="medicalImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="medicalPreview"
                                                            src="{{ isset($attachments) && $attachments->medical ? asset('storage/candidates/'.$attachments->medical) : asset('images/candidates/img1.jpg') }}"
                                                            alt="medical">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- NAVTEC Report --}}
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body">
                                                    <label class="form-label fw-semibold">
                                                        {{ __('NAVTEC Report') }}
                                                        <small class="text-danger">* ({{ __('Ratio') }} 4:3)</small>
                                                    </label>
                                                    <input type="file" name="navtec_report" id="navtecImageInput"
                                                        class="form-control"
                                                        accept=".jpg,.png,.jpeg,.gif,.bmp,.tif,.tiff">
                                                    <div class="text-center pt-3">
                                                        <img style="height:180px; border:1px solid #dee2e6; border-radius:10px; object-fit:cover;"
                                                            id="navtecPreview"
                                                            src="{{ isset($attachments) && $attachments->navtec_report ? asset('storage/candidates/'.$attachments->navtec_report) : asset('images/candidates/img1.jpg') }}"
                                                            alt="navtec">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-2">
                                            <button type="submit" class="btn btn-primary px-4">
                                                {{ isset($attachments) ? __('Update Documents') : __('Upload Documents') }}
                                            </button>
                                        </div>

                                    </div>{{-- /row --}}
                                </form>
                    </div></div>
            </div>
        </div>
    </div>

    {{-- OCR Results Modal --}}
    <div class="modal fade" id="ocrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🛂 {{ __('Passport OCR — Extracted Data') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ocrModalBody">
                    <p class="text-muted">{{ __('Scanning passport, please wait…') }}</p>
                </div>
                <div class="modal-footer" id="ocrModalFooter" style="display:none;">
                    <button class="btn btn-success" onclick="applyOcrFields()">✅ {{ __('Apply & Save') }}</button>
                    <button class="btn btn-secondary" data-dismiss="modal">{{ __('Dismiss') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('frontend_links')
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    <style>
        .ck-editor__editable_inline { min-height: 300px; }
        .ocr-field-row td { vertical-align: middle; }
        .conflict-row { background: #fff3cd; }
    </style>
@endsection

@section('frontend_scripts')
    @livewireScripts

    <script>
    /* ── Image preview helper ─────────────────────────────────────── */
    function previewImage(input, previewId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => document.getElementById(previewId).src = e.target.result;
        reader.readAsDataURL(file);
    }

    document.getElementById('passportImageInput').addEventListener('change', function() {
        previewImage(this, 'passportImagePreview');
    });
    document.getElementById('cnicFrontImageInput').addEventListener('change', function() {
        previewImage(this, 'cnicFrontPreview');
    });
    document.getElementById('cnicBackImageInput').addEventListener('change', function() {
        previewImage(this, 'cnicBackPreview');
    });
    document.getElementById('pPCImageInput').addEventListener('change', function() {
        previewImage(this, 'pPCPreview');
    });
    document.getElementById('medicalImageInput').addEventListener('change', function() {
        previewImage(this, 'medicalPreview');
    });
    document.getElementById('navtecImageInput').addEventListener('change', function() {
        previewImage(this, 'navtecPreview');
    });

    /* ── OCR Trigger (new file selected) ──────────────────────────── */
    let ocrData = null;
    let ocrConflicts = {};

    function triggerPassportOCR() {
        const fileInput = document.getElementById('passportImageInput');
        if (!fileInput.files.length) {
            alert('{{ __("Please select a passport image first.") }}');
            return;
        }
        const formData = new FormData();
        formData.append('passport', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');
        runOCR(formData);
    }

    function scanExistingPassport(imgUrl) {
        // When re-scanning an already-saved image we need to fetch it as a Blob
        const status = document.getElementById('ocrStatus');
        status.style.display = 'block';
        status.textContent = '{{ __("Fetching saved image for re-scan…") }}';

        fetch(imgUrl)
            .then(r => r.blob())
            .then(blob => {
                const formData = new FormData();
                formData.append('passport', blob, 'passport.jpg');
                formData.append('_token', '{{ csrf_token() }}');
                runOCR(formData);
            })
            .catch(() => {
                status.textContent = '{{ __("Could not fetch image.") }}';
            });
    }

    function runOCR(formData) {
        const status   = document.getElementById('ocrStatus');
        const $modal   = $('#ocrModal');
        const body     = document.getElementById('ocrModalBody');
        const footer   = document.getElementById('ocrModalFooter');

        status.style.display = 'block';
        status.textContent   = '⏳ {{ __("Scanning — please wait…") }}';
        body.innerHTML       = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">{{ __("Processing OCR…") }}</p></div>';
        footer.style.display = 'none';
        ocrData              = null;
        ocrConflicts         = {};
        $modal.modal('show');

        fetch('{{ route("ai.parse.passport") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(json => {
            status.style.display = 'none';
            if (json.error) {
                body.innerHTML = `<div class="alert alert-danger">${json.error}</div>`;
                return;
            }
            ocrData = json.extracted;
            ocrConflicts = json.conflicts || {};
            if (json.attachment_url) {
                document.getElementById('passportImagePreview').src = json.attachment_url;
            }
            body.innerHTML = buildOcrTable(json.extracted, json.conflicts || {});
            footer.style.display = '';
        })
        .catch(err => {
            body.innerHTML = `<div class="alert alert-danger">{{ __("OCR request failed.") }} ${err}</div>`;
            status.style.display = 'none';
        });
    }

    function buildOcrTable(extracted, conflicts) {
        const labels = {
            full_name:       '{{ __("Full Name") }}',
            surname:         '{{ __("Surname") }}',
            given_names:     '{{ __("Given Names") }}',
            nationality:     '{{ __("Nationality") }}',
            date_of_birth:   '{{ __("Date of Birth") }}',
            gender:          '{{ __("Gender") }}',
            place_of_birth:  '{{ __("Place of Birth") }}',
            date_of_issue:   '{{ __("Date of Issue") }}',
            date_of_expiry:  '{{ __("Date of Expiry") }}',
            place_of_issue:  '{{ __("Place of Issue") }}',
            passport_number: '{{ __("Passport Number") }}',
            mrz_line1:       'MRZ Line 1',
            mrz_line2:       'MRZ Line 2',
        };

        let rows = '';
        for (const [key, label] of Object.entries(labels)) {
            const val     = extracted[key] ?? '—';
            const conflict = conflicts[key];
            const rowClass = conflict ? 'class="conflict-row"' : '';
            const badge    = conflict ? `<span class="badge bg-warning text-dark ms-1">⚠️ {{ __("Conflict") }}</span>` : '';
            const dbVal    = conflict ? `<small class="text-muted d-block">{{ __("DB") }}: ${conflict.db}</small>` : '';
            rows += `<tr ${rowClass}>
                <td class="fw-semibold">${label}${badge}</td>
                <td><code>${val}</code>${dbVal}</td>
            </tr>`;
        }

        const conflictAlert = Object.keys(conflicts).length > 0
            ? `<div class="alert alert-warning">⚠️ {{ __("Conflicts detected — admin will review before applying.") }}</div>`
            : '';

        return `${conflictAlert}
        <table class="table table-sm table-bordered">
            <thead><tr><th>{{ __("Field") }}</th><th>{{ __("OCR Value") }}</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
        <p class="small text-muted">{{ __("Click Apply & Save to queue this for admin review and profile update.") }}</p>`;
    }

    function applyOcrFields() {
        $('#ocrModal').modal('hide');
        const hasConflicts = ocrConflicts && Object.keys(ocrConflicts).length > 0;
        alert(hasConflicts
            ? '{{ __("OCR data saved for admin review. Your profile will be updated once confirmed.") }}'
            : '{{ __("Passport data applied to your profile successfully.") }}');
    }
    </script>

@endsection
