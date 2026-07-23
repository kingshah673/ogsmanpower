@props(['industries', 'organizationTypes', 'teamsizes'])

@php
    $selectedIndustries = array_filter((array) request('industry_type'));
    $selectedOrganizations = array_filter((array) request('organization_type'));
    $selectedTeamSizes = array_filter((array) request('team_size'));
@endphp

<div class="modal fade cw-filter-drawer-modal cw-filter-drawer-modal--right cw-company-filter-modal" id="companyFiltersModal" tabindex="-1"
    aria-labelledby="companyFiltersModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog cw-filter-drawer modal-dialog-scrollable">
        <div class="modal-content">
            <div class="cw-filter-drawer__scroll">
                <div class="cw-filter-drawer__header">
                    <h2 class="cw-filter-drawer__title" id="companyFiltersModalLabel">{{ __('filter') }}</h2>
                    <button type="button" class="cw-filter-drawer__close" data-bs-dismiss="modal" aria-label="Close">
                        <x-svg.close-icon />
                    </button>
                </div>

                <div class="cw-filter-drawer__section">
                    <h3 class="cw-filter-drawer__label">{{ __('industry_type') }}</h3>
                    <select name="industry_type[]" id="company_filter_industry" class="form-control cw-filter-select2" multiple>
                        @foreach ($industries as $industry)
                            <option value="{{ $industry->name }}"
                                {{ in_array($industry->name, $selectedIndustries, true) ? 'selected' : '' }}>
                                {{ $industry->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="cw-filter-drawer__hint">{{ __('search') }} &amp; {{ __('select') }} {{ __('industry_type') }}</p>
                </div>

                <hr class="cw-filter-drawer__divider">

                <div class="cw-filter-drawer__section">
                    <h3 class="cw-filter-drawer__label">{{ __('organization_type') }}</h3>
                    <select name="organization_type[]" id="company_filter_organization" class="form-control cw-filter-select2" multiple>
                        @foreach ($organizationTypes as $organizationType)
                            <option value="{{ $organizationType->name }}"
                                {{ in_array($organizationType->name, $selectedOrganizations, true) ? 'selected' : '' }}>
                                {{ $organizationType->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="cw-filter-drawer__hint">{{ __('search') }} &amp; {{ __('select') }} {{ __('organization_type') }}</p>
                </div>

                <hr class="cw-filter-drawer__divider">

                <div class="cw-filter-drawer__section">
                    <h3 class="cw-filter-drawer__label">{{ __('team_size') }}</h3>
                    <select name="team_size[]" id="company_filter_team_size" class="form-control cw-filter-select2" multiple>
                        @foreach ($teamsizes as $teamsize)
                            <option value="{{ $teamsize->name }}"
                                {{ in_array($teamsize->name, $selectedTeamSizes, true) ? 'selected' : '' }}>
                                {{ $teamsize->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="cw-filter-drawer__hint">{{ __('search') }} &amp; {{ __('select') }} {{ __('team_size') }}</p>
                </div>
            </div>

            <div class="cw-filter-drawer__footer">
                <button type="button" class="btn btn-outline-secondary cw-filter-drawer__clear" id="companyClearFilters">
                    {{ __('clear') }}
                </button>
                <button type="submit" class="btn btn-primary">{{ __('apply_filter') }}</button>
            </div>
        </div>
    </div>
</div>

@push('frontend_scripts')
    <script>
        function companyFilterClearField(fieldName) {
            const $form = $('#company_search_form');
            $form.find('[name="' + fieldName + '[]"]').val(null).trigger('change');
            $form.find('[name="' + fieldName + '"]').val(null).trigger('change');
            $form.submit();
        }

        function initCompanyFilterSelect2() {
            const $modal = $('#companyFiltersModal');
            if (!$modal.length || typeof $.fn.select2 === 'undefined') {
                return;
            }

            const common = {
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $modal,
            };

            const multiFields = [
                { id: '#company_filter_industry', placeholder: '{{ __('select') }} {{ __('industry_type') }}' },
                { id: '#company_filter_organization', placeholder: '{{ __('select') }} {{ __('organization_type') }}' },
                { id: '#company_filter_team_size', placeholder: '{{ __('select') }} {{ __('team_size') }}' },
            ];

            multiFields.forEach(function(field) {
                const $el = $(field.id);
                if ($el.length && !$el.hasClass('select2-hidden-accessible')) {
                    $el.select2($.extend({}, common, {
                        placeholder: field.placeholder,
                        allowClear: true,
                        closeOnSelect: false,
                    }));
                }
            });
        }

        var companyFilterModal = document.getElementById('companyFiltersModal');
        if (companyFilterModal) {
            companyFilterModal.addEventListener('shown.bs.modal', function() {
                initCompanyFilterSelect2();
            });
        }

        $(document).ready(function() {
            initCompanyFilterSelect2();
        });

        var companyClearFilters = document.getElementById('companyClearFilters');
        if (companyClearFilters) {
            companyClearFilters.addEventListener('click', function() {
                ['#company_filter_industry', '#company_filter_organization', '#company_filter_team_size'].forEach(function(selector) {
                    const $el = $(selector);
                    if ($el.length) {
                        $el.val(null).trigger('change');
                    }
                });
            });
        }
    </script>
@endpush
