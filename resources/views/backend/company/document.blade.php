@extends('backend.layouts.app')
@section('title')
    {{ __('verification_documents') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title line-height-36 mb-0">{{ __('verification_documents') }} — {{ $company->user->name }}</h3>
                        <a href="{{ route('company.show', $company->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('back') }}</a>
                    </div>
                    <div class="card-body">
                        @include('backend.company.partials.verification-documents-card', [
                            'company' => $company,
                            'allDocumentTypes' => $allDocumentTypes ?? \App\Services\Company\CompanyDocumentVerificationService::allActiveDocumentTypes(),
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $('#document_verify_admin').on('change', function() {
        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: '{{ route('admin.document.verify.change', $company) }}',
            success: function(response) {
                toastr.success(response.message, 'Success');
                setTimeout(function () { window.location.reload(); }, 800);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || '{{ __('something_went_wrong') }}';
                toastr.error(message, 'Error');
                window.location.reload();
            }
        });
    });

    $('#requestResubmitForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '{{ route('admin.company.documents.request_resubmit', $company) }}',
            data: {
                _token: '{{ csrf_token() }}',
                document_review_note: $('#document_review_note').val()
            },
            success: function(response) {
                toastr.success(response.message, 'Success');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || '{{ __('something_went_wrong') }}';
                toastr.error(message, 'Error');
            }
        });
    });
</script>
<script>
    function syncAssignmentFieldState() {
        $('#companyDocumentAssignmentsForm .assignment-checkbox').each(function () {
            const enabled = $(this).is(':checked');
            const row = $(this).closest('.border.rounded.p-3');
            row.find('.assignment-type-id').prop('disabled', !enabled);
            row.find('.assignment-required').prop('disabled', !enabled);
        });
    }

    syncAssignmentFieldState();
    $(document).on('change', '.assignment-checkbox', syncAssignmentFieldState);

    $('#companyDocumentAssignmentsForm').on('submit', function (e) {
        e.preventDefault();
        const assignments = [];
        $('#companyDocumentAssignmentsForm .assignment-checkbox:checked').each(function () {
            const typeId = $(this).data('type-id');
            const row = $(this).closest('.border.rounded.p-3');
            assignments.push({
                document_type_id: typeId,
                is_required: row.find('.assignment-required').is(':checked') ? 1 : 0,
                sort_order: assignments.length + 1,
            });
        });

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '{{ route('admin.company.documents.assignments', $company) }}',
            data: {
                _token: '{{ csrf_token() }}',
                assignments: assignments,
            },
            success: function (response) {
                toastr.success(response.message, 'Success');
                setTimeout(function () { window.location.reload(); }, 700);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || '{{ __('something_went_wrong') }}';
                toastr.error(message, 'Error');
            }
        });
    });
</script>
@endsection
