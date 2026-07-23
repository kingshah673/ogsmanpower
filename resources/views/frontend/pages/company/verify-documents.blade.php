{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.company.layout.app')

@section('title')
    {{ __('employer_verification_documents_title') }}
@endsection

@section('css')
<style>
    .doc-verify-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        height: 100%;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }
    .doc-verify-card.is-uploaded {
        border-left: 4px solid #198754;
    }
    .doc-verify-card.is-missing {
        border-left: 4px solid #dc3545;
    }
    .doc-verify-card .doc-title {
        color: #0f172a;
        font-size: 1.05rem;
    }
    .doc-verify-card .doc-help {
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.45;
    }
    .doc-status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 999px;
    }
    .doc-status-badge.uploaded {
        background: #198754;
        color: #fff;
    }
    .doc-status-badge.missing {
        background: #dc3545;
        color: #fff;
    }
    .doc-saved-panel {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-left: 4px solid #198754;
        border-radius: 8px;
        padding: 0.875rem 1rem;
        margin-bottom: 1rem;
    }
    .doc-saved-panel .doc-saved-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.25rem;
    }
    .doc-saved-panel .doc-saved-name {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
        word-break: break-all;
        line-height: 1.4;
    }
    .doc-saved-panel .doc-saved-meta {
        color: #475569;
        font-size: 0.8125rem;
        margin-top: 0.35rem;
    }
    .doc-saved-panel .doc-view-btn {
        margin-top: 0.75rem;
        background: #fff;
        border: 1px solid #198754;
        color: #198754;
        font-weight: 600;
    }
    .doc-saved-panel .doc-view-btn:hover {
        background: #198754;
        color: #fff;
        border-color: #198754;
    }
    .doc-picked-panel {
        background: #eff6ff;
        border: 1px solid #93c5fd;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        color: #1e3a5f;
        font-size: 0.875rem;
    }
    .doc-picked-panel .picked-file-name {
        color: #0f172a;
        font-weight: 700;
    }
    .doc-save-btn {
        background: #ea580c;
        border: none;
        color: #fff !important;
        font-weight: 600;
        padding: 0.55rem 1rem;
        border-radius: 8px;
        opacity: 1 !important;
        visibility: visible !important;
    }
    .doc-save-btn:hover:not(:disabled) {
        background: #c2410c;
        color: #fff !important;
    }
    .doc-save-btn:disabled {
        background: #94a3b8 !important;
        color: #fff !important;
        cursor: not-allowed;
        opacity: 1 !important;
    }
    .doc-save-btn:disabled:hover {
        background: #94a3b8 !important;
        color: #fff !important;
    }
    .doc-replace-label {
        color: #334155;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .doc-file-input {
        border-color: #cbd5e1;
        font-size: 0.875rem;
    }
    .doc-file-input:focus {
        border-color: #ea580c;
        box-shadow: 0 0 0 0.2rem rgba(234, 88, 12, 0.15);
    }
</style>
@endsection

@section('main')
<div class="dashboard-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h4 class="fw-semibold text-dark mb-1">{{ __('employer_verification_documents_title') }}</h4>
                        <p class="text-muted small mb-3">{{ __('employer_verification_documents_intro') }}</p>
                        <hr>
                    </div>

                    <div class="card-body px-4 pb-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>{{ __('validation_errors') }}</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(($documentStatus ?? null) === \App\Services\Company\CompanyDocumentVerificationService::STATUS_RESUBMIT_REQUIRED)
                            <div class="alert alert-danger">
                                <strong>{{ __('employer_documents_resubmit_required') }}</strong>
                                @if($company->document_review_note)
                                    <p class="mb-0 mt-2">{{ $company->document_review_note }}</p>
                                @endif
                            </div>
                        @elseif(($documentStatus ?? null) === \App\Services\Company\CompanyDocumentVerificationService::STATUS_UPLOAD_REQUIRED)
                            <div class="alert alert-warning text-dark">{{ __('employer_documents_upload_required') }}</div>
                        @elseif(($documentStatus ?? null) === \App\Services\Company\CompanyDocumentVerificationService::STATUS_PENDING_APPROVAL)
                            <div class="alert alert-info text-dark">{{ __('employer_documents_pending_approval') }}</div>
                        @endif

                        @if(\App\Services\Company\CompanyDocumentVerificationService::isApproved($company))
                            <div class="alert alert-success text-dark mb-0">{{ __('employer_documents_verified_success') }}</div>
                        @else
                            <p class="text-muted small mb-3">{{ __('employer_per_document_upload_help') }}</p>

                            <div class="row g-4">
                                @foreach($documentSummary ?? [] as $type => $item)
                                    @php
                                        $isMissing = in_array($type, $missingDocuments ?? [], true);
                                        $previewUrl = $item['media']
                                            ? \App\Services\Company\CompanyDocumentVerificationService::documentPreviewUrl($company, $type, 'employer')
                                            : null;
                                    @endphp
                                    <div class="col-md-6" id="doc-{{ $type }}">
                                        <div class="doc-verify-card {{ $item['uploaded'] ? 'is-uploaded' : 'is-missing' }}">
                                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                <div class="doc-title fw-semibold">{{ $item['label'] }}</div>
                                                <span class="doc-status-badge {{ $item['uploaded'] ? 'uploaded' : 'missing' }}">
                                                    {{ $item['uploaded'] ? __('uploaded') : __('missing') }}
                                                </span>
                                            </div>
                                            <p class="doc-help mb-3">{{ $item['help'] }}</p>

                                            @if($item['media'])
                                                <div class="doc-saved-panel">
                                                    <div class="doc-saved-label">{{ __('saved_file_name') }}</div>
                                                    <div class="doc-saved-name">
                                                        <i class="fas fa-file-alt me-1 text-success"></i>{{ $item['media']->file_name }}
                                                    </div>
                                                    <div class="doc-saved-meta">
                                                        {{ $item['media']->created_at?->format('j M Y, g:i A') }}
                                                        · {{ number_format(($item['media']->size ?? 0) / 1024, 1) }} KB
                                                    </div>
                                                    @if($previewUrl)
                                                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-sm doc-view-btn">
                                                            <i class="fas fa-external-link-alt me-1"></i>{{ __('view_file') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif

                                            <form method="POST"
                                                  action="{{ route('company.verify.documents.store.single', $type) }}"
                                                  enctype="multipart/form-data"
                                                  class="doc-upload-form">
                                                @csrf
                                                <label class="doc-replace-label d-block mb-2" for="file-{{ $type }}">
                                                    {{ $item['uploaded'] ? __('replace_file') : __('choose_file') }}
                                                    @if($isMissing)<span class="text-danger">*</span>@endif
                                                </label>
                                                <input name="{{ $type }}"
                                                       id="file-{{ $type }}"
                                                       type="file"
                                                       accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,application/pdf"
                                                       class="form-control doc-file-input mb-2 @error($type) is-invalid @enderror"
                                                       data-type="{{ $type }}">
                                                @error($type)
                                                    <div class="text-danger small mb-2">{{ $message }}</div>
                                                @enderror

                                                <div class="doc-picked-panel mb-2 d-none" id="picked-{{ $type }}">
                                                    <i class="fas fa-paperclip me-1"></i>
                                                    <strong>{{ __('ready_to_upload') }}:</strong>
                                                    <span class="picked-file-name"></span>
                                                </div>

                                                <button type="submit" class="btn btn-sm w-100 doc-save-btn" disabled>
                                                    <i class="fas fa-save me-1"></i>
                                                    {{ $item['uploaded'] ? __('save_replacement') : __('save_this_document') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    document.querySelectorAll('.doc-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const type = input.dataset.type;
            const picked = document.getElementById('picked-' + type);
            const nameEl = picked ? picked.querySelector('.picked-file-name') : null;
            const btn = input.closest('form')?.querySelector('.doc-save-btn');

            if (!picked || !nameEl || !btn) return;

            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                nameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                picked.classList.remove('d-none');
                btn.disabled = false;
            } else {
                picked.classList.add('d-none');
                nameEl.textContent = '';
                btn.disabled = true;
            }
        });
    });

    document.querySelectorAll('.doc-upload-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.doc-save-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('uploading') }}...';
            }
        });
    });

    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 200);
        }
    }
})();
</script>
@endpush
