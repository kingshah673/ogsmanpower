@php
    use App\Services\Company\CompanyDocumentVerificationService;
    $documentSummary = CompanyDocumentVerificationService::documentUploadSummary($company);
    $allDocumentTypes = $allDocumentTypes ?? CompanyDocumentVerificationService::allActiveDocumentTypes();
    $assignedTypeIds = $company->verificationDocumentAssignments->pluck('document_type_id')->all();
@endphp

@if (userCan('company.update'))
    <div class="border rounded p-3 mb-4 bg-light">
        <h5 class="mb-2">{{ __('employer_manage_company_documents') }}</h5>
        <p class="text-muted small">{{ __('employer_manage_company_documents_help') }}</p>

        <form id="companyDocumentAssignmentsForm">
            @csrf
            <div class="row">
                @foreach($allDocumentTypes as $documentType)
                    @php
                        $assignment = $company->verificationDocumentAssignments->firstWhere('document_type_id', $documentType->id);
                        $isAssigned = in_array($documentType->id, $assignedTypeIds, true);
                    @endphp
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100 bg-white">
                            <label class="d-flex align-items-start gap-2 mb-2">
                                <input type="checkbox"
                                       class="assignment-checkbox mt-1"
                                       name="assignments[{{ $documentType->id }}][enabled]"
                                       value="1"
                                       data-type-id="{{ $documentType->id }}"
                                       {{ $isAssigned ? 'checked' : '' }}>
                                <span>
                                    <strong>{{ $documentType->label }}</strong>
                                    <small class="d-block text-muted">{{ $documentType->slug }}</small>
                                </span>
                            </label>
                            <input type="hidden" name="assignments[{{ $documentType->id }}][document_type_id]" value="{{ $documentType->id }}" disabled class="assignment-type-id">
                            <label class="small d-flex align-items-center gap-2 mb-0">
                                <input type="checkbox"
                                       name="assignments[{{ $documentType->id }}][is_required]"
                                       value="1"
                                       class="assignment-required"
                                       {{ ($assignment?->is_required ?? $documentType->is_required) ? 'checked' : '' }}
                                       {{ $isAssigned ? '' : 'disabled' }}>
                                {{ __('required') }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('save_document_assignments') }}</button>
            <a href="{{ route('admin.company.verification_document_types.index') }}" class="btn btn-outline-secondary btn-sm">
                {{ __('manage_document_field_types') }}
            </a>
        </form>
    </div>
@endif

<div class="row">
    @foreach($documentSummary as $type => $item)
        @php
            $media = $item['media'];
            $previewUrl = $media ? CompanyDocumentVerificationService::documentPreviewUrl($company, $type, 'admin') : null;
        @endphp
        <div class="col-md-6 mb-4">
            <div class="border rounded p-3 h-100 {{ $item['uploaded'] ? 'border-success' : 'border-warning' }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h5 class="mb-0">{{ $item['label'] }}</h5>
                    <span class="badge {{ $item['uploaded'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $item['uploaded'] ? __('uploaded') : __('missing') }}
                    </span>
                </div>
                <p class="text-muted small">{{ $item['help'] }}</p>

                @if(!$media)
                    <p class="text-muted small mb-0">{{ __('employer_documents_not_uploaded_yet') }}</p>
                @else
                    <p class="mb-1"><strong>{{ $media->file_name }}</strong></p>
                    <p class="text-muted small mb-2">
                        {{ $media->created_at?->format('j M Y, g:i A') }}
                        · {{ number_format(($media->size ?? 0) / 1024, 1) }} KB
                    </p>

                    @if($previewUrl && str_starts_with($media->mime_type ?? '', 'image/'))
                        <img src="{{ $previewUrl }}" alt="{{ $item['label'] }}" class="img-fluid rounded border mb-2" style="max-height: 260px;">
                    @elseif($previewUrl && (($media->mime_type ?? '') === 'application/pdf' || str_contains($media->mime_type ?? '', 'pdf')))
                        <iframe src="{{ $previewUrl }}" class="w-100 border rounded mb-2" style="height: 320px;"></iframe>
                    @elseif($previewUrl)
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mb-2">{{ __('view_file') }}</a>
                    @endif

                    <form action="{{ route('company.verify.documents.download', $company) }}" method="POST">
                        @csrf
                        <input type="hidden" name="file_type" value="{{ $type }}">
                        <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('download') }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if (userCan('company.update'))
    <div class="border-top pt-3 mt-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <label for="document_verify_admin" class="d-flex align-items-center mb-0">
                    <span class="mr-2">{{ __('approve_documents') }}</span>
                    <input id="document_verify_admin"
                           {{ $company->document_verified_at ? 'checked' : '' }}
                           type="checkbox" style="width: 24px; height: 24px;">
                </label>
                <p class="text-muted small mb-0 mt-2">
                    @if($company->document_verified_at)
                        {{ __('employer_documents_approved_on', ['date' => $company->document_verified_at->format('j M Y, g:i A')]) }}
                    @elseif(CompanyDocumentVerificationService::hasAllRequiredDocuments($company))
                        {{ __('employer_documents_pending_admin_review') }}
                    @else
                        {{ __('employer_documents_missing_cannot_approve') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.company.documents', $company) }}" class="btn btn-sm btn-outline-secondary">
                {{ __('open_full_document_review') }}
            </a>
        </div>

        <form id="requestResubmitForm" class="mt-3">
            @csrf
            <label for="document_review_note" class="font-weight-bold">{{ __('employer_request_document_resubmit') }}</label>
            <textarea id="document_review_note" name="document_review_note" rows="3" class="form-control mb-2"
                placeholder="{{ __('employer_request_document_resubmit_placeholder') }}">{{ old('document_review_note', $company->document_review_note) }}</textarea>
            <button type="submit" class="btn btn-warning">{{ __('employer_send_resubmit_request') }}</button>
            <p class="text-muted small mt-2 mb-0">{{ __('employer_request_document_resubmit_help') }}</p>
        </form>
    </div>
@endif
