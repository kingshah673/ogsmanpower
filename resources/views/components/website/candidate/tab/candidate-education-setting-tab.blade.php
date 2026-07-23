@props(['educations'])
<div class="card tw-mb-4">
<div class="card-body">
<div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
    <h3 class="f-size-18 lh-1 m-0">{{ __('educations') }}</h3>
    {{-- @if($educations !='')
    <button id="addEducation" type="button" class="btn btn-primary addEducation ">
        {{ __('add_education') }}
    </button>
    @endif --}}
    <button type="button" id="educationtoggleForm" class="btn btn-icon tw-ml-4 ">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="#007BFF" stroke-width="2" />
            <path d="M12 7v10M7 12h10" stroke="#007BFF" stroke-width="2" />
        </svg>
    </button>
</div>
<div class="tw-flex tw-items-center tw-gap-4 d-none" id="educationPreview">
    <div class="">

        <table class="tw-px-2">
            <thead>
                <tr>
                    <th class="!tw-text-base !tw-font-medium">{{ __('Education') }}</th>
                    <th class="!tw-text-base !tw-font-medium">{{ __('degree') }}</th>
                    <th class="!tw-text-base !tw-font-medium">{{ __('year') }}</th>

                </tr>
            </thead>
            <tbody>
                @foreach ($educations as $education)
                    <tr>
                        <td>{{ $education->level }}</td>
                        <td>{{ $education->degree }}</td>
                        <td>{{ $education->year }}</td>

                    </tr>

                    @endforeach
            </tbody>
        </table>


    </div>
</div>
<div id="educationForm" class="db-job-card-table -tw-mx-2" style="overflow-x:auto">
    <table class="tw-px-2 w-100" style="min-width:480px">
        <thead>
            <tr>
                <th class="!tw-text-base !tw-font-medium" style="min-width:140px">{{ __('education_level') }}</th>
                <th class="!tw-text-base !tw-font-medium" style="min-width:180px">{{ __('degree') }}</th>
                <th class="!tw-text-base !tw-font-medium" style="width:70px">{{ __('year') }}</th>
                <th class="!tw-text-base !tw-font-medium text-center" style="width:90px">{{ __('action') }}</th>
            </tr>
        </thead>
        <tbody id="cwEducationRows">
            @forelse ($educations as $education)
                <tr>
                    <td>{{ $education->level }}</td>
                    <td>{{ $education->degree }}</td>
                    <td>{{ $education->year }}</td>
                    <td class="text-center text-nowrap">
                        <a href="javascript:void(0)"
                            class="btn btn-sm btn-outline-primary me-1"
                            title="{{ __('edit') }}"
                            onclick="educationDetail({{ json_encode($education) }})">
                            <x-svg.edit-icon />
                        </a>
                        <form method="POST" action="{{ route('candidate.educations.destroy', $education->id) }}" class="d-inline">
                            @csrf
                            @method('Delete')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('delete') }}"
                                onclick="return confirm('{{ __('are_you_sure_you_want_to_delete_this_item') }}');">
                                <x-svg.trash-icon />
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <x-svg.not-found-icon />
                        <p class="mt-2">{{ __('no_data_found') }}</p>
                    </td>
                </tr>
            @endforelse
            <tr>
                <td colspan="4" class="pt-3">
                    <button id="addEducation" type="button" class="btn btn-primary addEducation">
                        {{ __('add_education') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
</div>

@push('frontend_links')
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <style>
        #addEducationModal .modal-dialog,
        #editEducationModal .modal-dialog {
            z-index: 999999 !important;
            max-width: 950px !important;
            padding: 20px;
        }
    </style>
@endpush

@push('frontend_scripts')
<script>
    document.getElementById('educationtoggleForm').addEventListener('click', function () {
        const form = document.getElementById('educationForm');
        const preview = document.getElementById('educationPreview');
        form.classList.toggle('d-none');
        preview.classList.toggle('d-none');
    });
</script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    @if (app()->getLocale() == 'ar')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                    "></script>
    @endif
    <script>
        $('.addEducation').on('click', function() {
            $('#addEducationModal').modal('show');
        });

        //  $(".year_picker").attr("autocomplete", "off");

        //init datepicker
        // $('.year_picker').off('focus').datepicker({
        //     format: "yyyy",
        //     viewMode: "years",
        //     minViewMode: "years",
        //     isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
        //     language: "{{ app()->getLocale() }}",
        // }).on('click',
        //     function() {
        //         $(this).datepicker('show');
        //     }
        // );

        $('.year_picker').datepicker({
            format: 'yyyy',
            viewMode: "years",
            minViewMode: "years",
            autoclose: true
        });

        function closeAddEducationModal() {
            $('#addEducationModal').find('form')[0].reset();
            $('#addEducationModal').modal('hide')
        }

        function closeEditEducationModal() {
            $('#editEducationModal').find('form')[0].reset();
            $('#editEducationModal').modal('hide')
        }

        function educationDetail(education, start, end) {
            $('#education-modal-id').val(education.id);
            $('#education-modal-level').val(education.level);
            $('#education-modal-degree').val(education.degree);
            $('#education-modal-year').val(education.year);
            $('#education-notes').val(education.notes);

            $('#editEducationModal').modal('show');
        }
        window.educationDetail = educationDetail;
    </script>
@endpush
