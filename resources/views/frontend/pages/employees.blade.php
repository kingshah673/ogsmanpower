@extends('frontend.layouts.app')

@section('description')
    @php
        $data = metaData('company');
    @endphp
    {{ $data->description }}
@endsection
@section('og:image')
    {{ asset($data->image) }}
@endsection
@section('title')
    {{ $data->title }}
@endsection

@section('main')
    <form action="{{ route('website.company') }}" method="GET" id="company_search_form">
        <x-website.company.company-filtering :industries="$industries" :organization-types="$organization_types"
            :teamsizes="$teamsizes" />

        <div class="cw-jobs-portal">
            <div class="container">
                <div class="cw-jobs-portal__listing-head">
                    <div>
                        <h2 class="cw-jobs-portal__listing-title">
                            {{ __('companies') }}
                            <span class="cw-jobs-portal__count-badge">
                                {{ $companies->total() }} {{ __('companies') }}
                            </span>
                        </h2>
                        <p class="cw-jobs-portal__listing-sub">{{ __('find_employers') }}</p>
                    </div>
                    <div class="cw-jobs-portal__view-toggle">
                        <button type="button" class="cw-jobs-portal__view-btn active" id="nav-home-tab"
                            data-bs-toggle="tab" data-bs-target="#nav-home" onclick="styleSwitch('box')">
                            <x-svg.box-icon />
                        </button>
                        <button type="button" class="cw-jobs-portal__view-btn" id="nav-profile-tab"
                            data-bs-toggle="tab" data-bs-target="#nav-profile" onclick="styleSwitch('list')">
                            <x-svg.list-icon />
                        </button>
                    </div>
                </div>

                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane show active" id="nav-home" role="tabpanel">
                        <div class="cw-jobs-portal__grid">
                            @forelse ($companies as $company)
                                <x-website.company.company-card :company="$company" />
                            @empty
                                <div class="tw-col-span-full">
                                    <div class="card text-center">
                                        <x-not-found message="{{ __('no_data_found') }}" />
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="tab-pane" id="nav-profile" role="tabpanel">
                        <div class="cw-portal-list">
                            @forelse ($companies as $company)
                                <x-website.company.company-card :company="$company" variant="list" />
                            @empty
                                <div class="card text-center">
                                    <x-not-found message="{{ __('no_data_found') }}" />
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if ($companies->count())
                    <div class="cw-jobs-portal__pagination">
                        {{ $companies->links('vendor.pagination.frontend') }}
                    </div>
                @endif
            </div>
        </div>
    </form>

    <div class="rt-spacer-100 rt-spacer-md-50"></div>
    <x-website.subscribe-newsletter />
@endsection

@push('frontend_scripts')
    <script>
        var style = localStorage.getItem('job_style') == null ? 'box' : localStorage.getItem('job_style');
        setStyle(style);

        function styleSwitch(companystyle) {
            localStorage.setItem('job_style', companystyle);
            setStyle(companystyle);
        }

        function setStyle(style) {
            if (style == 'box') {
                $('#nav-home-tab').addClass('active');
                $('#nav-home').addClass('show active');
                $('#nav-profile-tab').removeClass('active');
                $('#nav-profile').removeClass('show active');
            } else {
                $('#nav-home-tab').removeClass('active');
                $('#nav-home').removeClass('show active');
                $('#nav-profile-tab').addClass('active');
                $('#nav-profile').addClass('show active');
            }
        }
    </script>
@endpush
