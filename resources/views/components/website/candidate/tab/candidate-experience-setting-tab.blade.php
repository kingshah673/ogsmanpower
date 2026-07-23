@props(['experiences'])
<div class="card tw-mb-4">

<div class="card-body">
<div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
    <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">{{ __('experience') }}</h3>
    {{-- @if($experiences)
    <button id="addExperience"  type="button" class="btn btn-primary addExperience">
        {{ __('add_experience') }}
    </button>
    @endif --}}
    <button type="button" id="experiencetoggleForm" class="btn btn-icon tw-ml-4 ">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="#007BFF" stroke-width="2" />
            <path d="M12 7v10M7 12h10" stroke="#007BFF" stroke-width="2" />
        </svg>
    </button>
</div>
<div class="tw-flex tw-items-center tw-gap-4 d-none" id="experiencePreview">
    <div class="">

        <table class="tw-px-2">
            <thead>
                <tr>
                    <th class="!tw-text-base !tw-font-medium">{{ __('company') }}</th>
                    <th class="!tw-text-base !tw-font-medium">{{ __('department') }}</th>
                    <th class="!tw-text-base !tw-font-medium">{{ __('designation') }}</th>
                    <th class="!tw-text-base !tw-font-medium">{{ __('period') }}</th>

                </tr>
            </thead>
            <tbody>
                @foreach ($experiences as $experience)
                    <tr>
                        <td>{{ $experience->company }}</td>
                        <td>{{ $experience->department }}</td>
                        <td>{{ $experience->designation }}</td>
                        <td>
                            {{ formatTime($experience->start, 'd M Y') }} -
                            {{ $experience->currently_working ? __('currently_working') : formatTime($experience->end, 'd M Y') }}
                        </td>

                    </tr>

                @endforeach

            </tbody>
        </table>


    </div>
</div>
<div id="experienceForm" class="db-job-card-table -tw-mx-2 tw-pb-16" style="overflow-x:auto">
    <table class="tw-px-2 w-100" style="min-width:600px">
        <thead>
            <tr>
                <th class="!tw-text-base !tw-font-medium" style="min-width:130px">{{ __('company') }}</th>
                <th class="!tw-text-base !tw-font-medium" style="min-width:110px">{{ __('department') }}</th>
                <th class="!tw-text-base !tw-font-medium" style="min-width:150px">{{ __('designation') }}</th>
                <th class="!tw-text-base !tw-font-medium" style="min-width:150px">{{ __('period') }}</th>
                <th class="!tw-text-base !tw-font-medium text-center" style="width:90px">{{ __('action') }}</th>
            </tr>
        </thead>
        <tbody id="cwExperienceRows">
            @forelse ($experiences as $experience)
                <tr>
                    <td>{{ $experience->company }}</td>
                    <td>{{ $experience->department }}</td>
                    <td>{{ $experience->designation }}</td>
                    <td class="text-nowrap">
                        {{ formatTime($experience->start, 'd M Y') }} –
                        {{ $experience->currently_working ? __('currently_working') : formatTime($experience->end, 'd M Y') }}
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="javascript:void(0)"
                            class="btn btn-sm btn-outline-primary me-1"
                            title="{{ __('edit') }}"
                            onclick="experienceDetail({{ json_encode($experience) }}, '{{ $experience->start ? date('d-m-Y', strtotime($experience->start)) : '' }}', '{{ $experience->end ? date('d-m-Y', strtotime($experience->end)) : '' }}')">
                            <x-svg.edit-icon />
                        </a>
                        <form method="POST" action="{{ route('candidate.experiences.destroy', $experience->id) }}" class="d-inline">
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
                    <td colspan="5" class="text-center py-4">
                        <x-svg.not-found-icon />
                        <p class="mt-2">{{ __('no_data_found') }}</p>
                    </td>
                </tr>
            @endforelse
            <tr>
                <td colspan="5" class="pt-3">
                    <button id="addExperience" type="button" class="btn btn-primary addExperience">
                        {{ __('add_experience') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
</div>
</div>
@push('frontend_links')
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <style>
        #addExperienceModal .modal-dialog,
        #editExperienceModal .modal-dialog {
            z-index: 999999 !important;
            max-width: 950px !important;
            padding: 20px !important;
        }
    </style>
@endpush

@push('frontend_scripts')
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    @if (app()->getLocale() == 'ar')
        <script defer src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
            "></script>
    @endif
    <script>
        document.getElementById('experiencetoggleForm').addEventListener('click', function () {
            const form = document.getElementById('experienceForm');
            const preview = document.getElementById('experiencePreview');
            form.classList.toggle('d-none');
            preview.classList.toggle('d-none');
        });
    </script>
    <script>
        $('.addExperience').on('click', function() {
            $('#addExperienceModal').modal('show');
        });

        //  $(".date_picker").attr("autocomplete", "off");

        // //init datepicker
        // $('.date_picker').off('focus').datepicker({
        //     format: 'd-m-yyyy',
        //     isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
        //     language: "{{ app()->getLocale() }}",
        // }).on('click',
        //     function() {
        //         $(this).datepicker('show');
        //     }
        // );
        $('.date_picker').datepicker({
            format: "yyyy-mm-dd",
            autoclose: true
        });

        function closeAddExperienceModal() {
            $('#addExperienceModal').find('form')[0].reset();
            $('#addExperienceModal').modal('hide')
        }

        function closeEditExperienceModal() {
            $('#editExperienceModal').find('form')[0].reset();
            $('#editExperienceModal').modal('hide')
        }

        function experienceDetail(experience, start, end) {
            $('#experience-modal-id').val(experience.id);
            $('#experience-modal-company').val(experience.company);
            $('#experience-modal-department').val(experience.department);
            $('#experience-modal-designation').val(experience.designation);
            $('#experience-modal-start').val(start);
            $('#experience-modal-end').val(end);
            $('#experience-responsibilities').val(experience.responsibilities);
            $('#experience-modal-checkbox_edit').prop("checked", experience.currently_working ? true : false);

            $('#editExperienceModal').modal('show');
        }
        window.experienceDetail = experienceDetail;
    </script>
@endpush
