@extends('backend.layouts.app')
@section('title', __('employer_verification_document_types_title'))

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">{{ __('employer_verification_document_types_title') }}</h4>
                    <p class="text-muted mb-0 small">{{ __('employer_verification_document_types_intro') }}</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#documentTypeModal">
                    {{ __('add_document_field') }}
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('label') }}</th>
                            <th>{{ __('slug') }}</th>
                            <th>{{ __('required') }}</th>
                            <th>{{ __('default_for_new_companies') }}</th>
                            <th>{{ __('active') }}</th>
                            <th>{{ __('sort_order') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documentTypes as $documentType)
                            <tr>
                                <td>
                                    <strong>{{ $documentType->label }}</strong>
                                    @if($documentType->help_text)
                                        <div class="small text-muted">{{ $documentType->help_text }}</div>
                                    @endif
                                </td>
                                <td><code>{{ $documentType->slug }}</code></td>
                                <td>{{ $documentType->is_required ? __('yes') : __('no') }}</td>
                                <td>{{ $documentType->is_default ? __('yes') : __('no') }}</td>
                                <td>
                                    <input type="checkbox" class="toggle-type-active" data-id="{{ $documentType->id }}" {{ $documentType->is_active ? 'checked' : '' }}>
                                </td>
                                <td>{{ $documentType->sort_order }}</td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-document-type"
                                        data-id="{{ $documentType->id }}"
                                        data-label="{{ $documentType->label }}"
                                        data-help="{{ $documentType->help_text }}"
                                        data-slug="{{ $documentType->slug }}"
                                        data-required="{{ $documentType->is_required ? 1 : 0 }}"
                                        data-default="{{ $documentType->is_default ? 1 : 0 }}"
                                        data-active="{{ $documentType->is_active ? 1 : 0 }}"
                                        data-sort="{{ $documentType->sort_order }}">
                                        {{ __('edit') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-document-type" data-id="{{ $documentType->id }}">
                                        {{ __('delete') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('no_document_fields_yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="documentTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentTypeModalTitle">{{ __('add_document_field') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="documentTypeId">
                    <div class="form-group">
                        <label for="documentTypeLabel">{{ __('label') }}</label>
                        <input type="text" id="documentTypeLabel" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="documentTypeSlug">{{ __('slug') }}</label>
                        <input type="text" id="documentTypeSlug" class="form-control" placeholder="trade_license">
                        <small class="text-muted">{{ __('document_field_slug_help') }}</small>
                    </div>
                    <div class="form-group">
                        <label for="documentTypeHelp">{{ __('help_text') }}</label>
                        <textarea id="documentTypeHelp" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="documentTypeRequired">{{ __('required') }}</label>
                            <select id="documentTypeRequired" class="form-control">
                                <option value="1">{{ __('yes') }}</option>
                                <option value="0">{{ __('no') }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="documentTypeDefault">{{ __('default_for_new_companies') }}</label>
                            <select id="documentTypeDefault" class="form-control">
                                <option value="1">{{ __('yes') }}</option>
                                <option value="0">{{ __('no') }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="documentTypeSort">{{ __('sort_order') }}</label>
                            <input type="number" id="documentTypeSort" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveDocumentType">{{ __('save') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    function resetDocumentTypeForm() {
        $('#documentTypeId').val('');
        $('#documentTypeLabel').val('');
        $('#documentTypeSlug').val('');
        $('#documentTypeHelp').val('');
        $('#documentTypeRequired').val('1');
        $('#documentTypeDefault').val('0');
        $('#documentTypeSort').val('0');
        $('#documentTypeModalTitle').text(@json(__('add_document_field')));
    }

    $('[data-target="#documentTypeModal"]').on('click', resetDocumentTypeForm);

    $('.edit-document-type').on('click', function () {
        const btn = $(this);
        $('#documentTypeId').val(btn.data('id'));
        $('#documentTypeLabel').val(btn.data('label'));
        $('#documentTypeSlug').val(btn.data('slug'));
        $('#documentTypeHelp').val(btn.data('help'));
        $('#documentTypeRequired').val(String(btn.data('required')));
        $('#documentTypeDefault').val(String(btn.data('default')));
        $('#documentTypeSort').val(btn.data('sort'));
        $('#documentTypeModalTitle').text(@json(__('edit_document_field')));
        $('#documentTypeModal').modal('show');
    });

    $('#saveDocumentType').on('click', function () {
        const id = $('#documentTypeId').val();
        const url = id
            ? '{{ url('/admin/company/verification-document-types') }}/' + id
            : '{{ route('admin.company.verification_document_types.store') }}';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                label: $('#documentTypeLabel').val(),
                slug: $('#documentTypeSlug').val(),
                help_text: $('#documentTypeHelp').val(),
                is_required: $('#documentTypeRequired').val(),
                is_default: $('#documentTypeDefault').val(),
                sort_order: $('#documentTypeSort').val(),
            },
            success: function () {
                toastr.success(@json(__('company_updated_successfully')));
                window.location.reload();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || @json(__('something_went_wrong')));
            }
        });
    });

    $('.delete-document-type').on('click', function () {
        if (!confirm(@json(__('are_you_sure_you_want_to_delete_this_item')))) return;

        $.ajax({
            url: '{{ url('/admin/company/verification-document-types') }}/' + $(this).data('id'),
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                toastr.success(@json(__('company_deleted_successfully')));
                window.location.reload();
            }
        });
    });

    $('.toggle-type-active').on('change', function () {
        $.post('{{ url('/admin/company/verification-document-types') }}/' + $(this).data('id') + '/toggle-active', {
            _token: '{{ csrf_token() }}',
            is_active: $(this).is(':checked') ? 1 : 0
        });
    });
</script>
@endsection
