<div class="ai-upload-card" id="job-ad-upload-sec">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <span class="cw-ai-badge">AI Co-Pilot</span>
            <h4 class="mb-1">{{ __('Smart Job Auto-Fill') }}</h4>
            <p class="text-light mb-0">
                {{ __('Upload a job advertisement PDF or image (any language). Single ads fill the form; multi-position ads can be posted in one click.') }}
            </p>
        </div>
        <div id="jobAiLoader" class="d-none bg-light border rounded px-3 py-1 small text-muted">
            <i class="fas fa-spinner fa-spin mr-1 text-primary"></i> {{ __('Processing') }}...
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mb-3 mb-md-0">
            <div class="upload-box-glass text-center" onclick="triggerJobDocUpload()">
                <div class="upload-inner">
                    <div class="upload-icon"><i class="fas fa-file-alt"></i></div>
                    <p id="jobDocText" class="mb-1">{{ __('Select job advertisement PDF or image') }}</p>
                    <small>PDF, JPG, PNG, WebP — {{ __('any language') }}</small>
                </div>
                <input type="file" id="jobDocUpload" accept=".pdf,.jpg,.jpeg,.png,.webp" hidden
                    onchange="onJobDocSelected(this)">
            </div>
            <button type="button" onclick="uploadJobPosting()" class="btn btn-ai w-100 mt-2">
                <i class="fas fa-magic mr-1"></i> {{ __('Extract & Autofill Job') }}
            </button>
        </div>
        <div class="col-md-4">
            <div class="extraction-summary-card border rounded p-3 h-100" id="jobExtractionSummary" style="display:none;">
                <h6 class="mb-2 text-success"><i class="fa fa-check-circle mr-1"></i> {{ __('Fields filled') }}</h6>
                <div id="jobFilledList" class="small text-muted"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="jobBatchModal" tabindex="-1" aria-labelledby="jobBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobBatchModalLabel">{{ __('Multiple jobs detected') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="jobBatchModalIntro"></p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Job title') }}</th>
                                <th>{{ __('Vacancies') }}</th>
                                <th>{{ __('Salary') }}</th>
                                <th>{{ __('Location') }}</th>
                            </tr>
                        </thead>
                        <tbody id="jobBatchTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer flex-wrap">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-outline-primary" id="jobBatchFillFirstBtn">{{ __('Fill first job in form') }}</button>
                <button type="button" class="btn btn-primary" id="jobBatchPostAllBtn">
                    <i class="fas fa-upload mr-1"></i> <span id="jobBatchPostAllLabel">{{ __('Post all jobs') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('frontend_scripts')
    <script src="{{ asset('js/company-job-ai-upload.js') }}?v={{ @filemtime(public_path('js/company-job-ai-upload.js')) ?: '1' }}"></script>
    <script>
        window.cwJobAiParseUrl = @json(route('ai.parse.job'));
        window.cwJobAiBatchStoreUrl = @json(route('company.job.store.parsed'));
    </script>
@endpush
