@extends('components.website.company.layout.app')

@section('title', 'Assign Agencies')

@section('main')

<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

<style>

.filter-card{
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.05);
}

.ts-control{
    min-height:52px !important;
    padding:10px !important;
}

.badge-agency{
    background:#eef2ff;
    color:#3730a3;
    padding:8px 14px;
    border-radius:30px;
    font-size:13px;
    font-weight:600;
}

.info-box{
    background:#f9fafb;
    border-radius:12px;
    padding:18px;
}

.ts-wrapper.multi .ts-control {

    min-height: 65px !important;

    padding: 12px !important;

    border-radius: 14px !important;

    border: 1px solid #d1d5db !important;

    font-size: 15px !important;
}

.ts-dropdown {

    border-radius: 14px !important;

    overflow: hidden;

    border: 1px solid #e5e7eb !important;

    box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important;
}

.ts-dropdown .option {

    padding: 16px !important;

    border-bottom: 1px solid #f3f4f6;

    font-size: 15px !important;

    line-height: 1.8;
}

.ts-dropdown .option:hover {

    background: #f9fafb !important;
}

.ts-control input {

    font-size: 15px !important;
}


</style>

<div class="container py-4">

    <div class="card filter-card">

        <div class="card-body p-4">

            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h3 class="fw-bold mb-1">

                        Assign Agencies

                    </h3>

                    <p class="text-muted mb-0">

                        Assign recruitment agencies to your job posting.

                    </p>

                </div>

                <a href="{{ route('company.myjob') }}"
                   class="btn btn-outline-dark">

                    Back

                </a>

            </div>

            <!-- JOB INFO -->

            <div class="info-box mb-4">

                <div class="row">

                    <div class="col-md-4">

                        <small class="text-muted">
                            Job Title
                        </small>

                        <div class="fw-bold">
                            {{ $job->title }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <small class="text-muted">
                            Job Status
                        </small>

                        <div class="text-capitalize fw-bold">
                            {{ $job->status }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <small class="text-muted">
                            Plan Agency Limit
                        </small>

                        <div class="fw-bold">
                            {{ $agencyLimit }} Agencies
                        </div>

                    </div>

                </div>

            </div>

            <!-- FILTERS -->

            <div class="card border-0 bg-light mb-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-3">

                        Filter Agencies

                    </h5>

                    <div class="row g-3">

                        <!-- COUNTRY -->

                        <div class="col-md-4">

                            <label class="form-label">

                                Country

                            </label>

                            <select id="country-filter"
                                    class="form-control">

                                <option value="">
                                    All Countries
                                </option>

                                @foreach($agencies->pluck('country')->unique() as $country)

                                    @if($country)

                                        <option value="{{ strtolower($country) }}">

                                            {{ $country }}

                                        </option>

                                    @endif

                                @endforeach

                            </select>

                        </div>

                        <!-- SEARCH -->

                        <div class="col-md-4">

                            <label class="form-label">

                                Search Agency

                            </label>

                            <input type="text"
                                   id="search-filter"
                                   class="form-control"
                                   placeholder="Search agency name...">

                        </div>

                        <!-- TOTAL -->

                        <div class="col-md-4">

                            <label class="form-label">

                                Total Agencies

                            </label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ $agencies->count() }} Agencies Available"
                                   readonly>

                        </div>

                    </div>

                </div>

            </div>

            <!-- FORM -->

            <form action="{{ route('company.job.assign.agency.store', $job->id) }}"
                  method="POST">

                @csrf

                <!-- AGENCY SELECT -->

                <div class="mb-4">

    <label class="form-label fw-bold fs-5 mb-3">

        Select Agencies

    </label>

    <div class="border rounded-4 p-3 bg-light">

        <select id="agency-select"
                name="agency_ids[]"
                multiple
                required>

            @foreach($agencies as $agency)

                <option value="{{ $agency->id }}"

                    data-country="{{ strtolower($agency->country ?? '') }}"

                    data-name="{{ strtolower($agency->user->name ?? '') }}"

                    @if($job->agencies->contains($agency->id))
                        selected
                    @endif
                >

                    {{ $agency->user->name ?? 'No Name' }}
                    | ⭐ Rating: 5.0
                    | 🌍 {{ $agency->country ?? 'Unknown Country' }}

                </option>

            @endforeach

        </select>

    </div>

    <div class="mt-3">

        <div class="alert alert-info mb-0">

            <strong>Tips:</strong>

            <ul class="mb-0 mt-2">

                <li>
                    Search agencies using name or country.
                </li>

                <li>
                    You can select maximum
                    <strong>{{ $agencyLimit }}</strong>
                    agencies according to your current plan.
                </li>

                <li>
                    Selected agencies will receive this job assignment instantly.
                </li>

            </ul>

        </div>

    </div>

</div>

                <!-- ASSIGNED -->

                @if($job->agencies->count())

                    <div class="mb-4">

                        <h6 class="fw-bold mb-3">

                            Currently Assigned Agencies

                        </h6>

                        <div class="d-flex flex-wrap gap-2">

                            @foreach($job->agencies as $assignedAgency)

                                <span class="badge-agency">

                                    {{ $assignedAgency->user->name ?? 'No Name' }}

                                </span>

                            @endforeach

                        </div>

                    </div>

                @endif

                <!-- BUTTON -->

                <button type="submit"
                        class="btn btn-success px-4">

                    Save Agencies

                </button>

            </form>

        </div>

    </div>

</div>

<script>

    const agencySelect = new TomSelect("#agency-select", {

        plugins:['remove_button'],

        create:false,

        persist:false,

        maxItems: {{ $agencyLimit }},

        placeholder:"Search agencies...",

        searchField:['text'],

        render: {

            option: function(data, escape) {

                return `
                    <div style="padding:14px;">
                        <div style="font-weight:600;font-size:15px;">
                            ${escape(data.text)}
                        </div>
                    </div>
                `;
            }

        }

    });

    const originalOptions = [

        @foreach($agencies as $agency)

        {
            value: "{{ $agency->id }}",

            text: "{{ $agency->user->name ?? 'No Name' }} — ⭐ 5.0 — {{ $agency->country ?? 'Unknown Country' }}",

            country: "{{ strtolower($agency->country ?? '') }}",

            name: "{{ strtolower($agency->user->name ?? '') }}"
        },

        @endforeach

    ];

    function filterAgencies()
    {
        let country = document.getElementById('country-filter').value.toLowerCase();

        let search = document.getElementById('search-filter').value.toLowerCase();

        agencySelect.clearOptions();

        originalOptions.forEach(option => {

            let show = true;

            if(country && option.country !== country)
            {
                show = false;
            }

            if(search && !option.name.includes(search))
            {
                show = false;
            }

            if(show)
            {
                agencySelect.addOption(option);
            }

        });

        agencySelect.refreshOptions(false);
    }

    document.getElementById('country-filter')
        .addEventListener('change', filterAgencies);

    document.getElementById('search-filter')
        .addEventListener('keyup', filterAgencies);

</script>

@endsection