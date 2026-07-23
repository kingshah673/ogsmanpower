{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agency.layout.app')


@section('title', __('dashboard'))

@section('main')

    <div class="mblscreen">

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image">
                    <img src={{ asset(auth()->user()->agency->logo) }} alt="Profile Image">
                </div>
                <div class="profile-details">
                    @if (auth()->user()->agency->is_profile_verified == 1)
                        <h2>{{ auth()->user()->name }} <i class="fas fa-check-circle verification"></i></h2>
                    @else
                        <h2>{{ auth()->user()->name }} </h2>
                    @endif
                    <p>{{ ucfirst(auth()->user()->agency->address) }}</p>
                    <p><i class="fas fa-phone"></i> {{ auth()->user()->whatsapp }}</p>
                    <p><i class="fas fa-envelope"></i> {{ auth()->user()->email }}</p>
                </div>

            </div>
            <div class="profile-actions">
                <button class="unviewed-cv">Unviewed CV</button>
                <button class="cv-since-login">CV’s since last login</button>
                <button class="new-cvs-today">New CV’s Today</button>
            </div>
        </div>




        <div>
            <canvas id="dailyChart" style="width: 100%; max-width: 600px;"></canvas>
        </div>



        <div class="job-listing ">
            <div class="  tw-w-full mt-2" style="background-color: #4576D3">
                <h3 class="f-size-18 text-white lh-1 mb-0 tw-inline-flex ">
                    {{ __('my_jobs') }}
                    <span class="text-white-400">({{ $recentJobs->count() }})</span>
                </h3>
            </div>

            @if ($recentJobs->count() > 0)
                @foreach ($recentJobs as $job)
                    <div class="header">
                        <div class="job-header">
                            <a href="{{ route('website.job.details', $job->slug) }}" class="job-header-title">
                                <h5> {{ $job->title }}</h5>
                            </a>
                            <div class="body-font-4 text-gray-600 pt-2">
                                <span class="info-tools rt-mr-8">
                                    {{ ucfirst($job->job_type->name) }}
                                </span>
                                <span class="" style="font-size: 11px">
                                    {{ $job->days_remaining }}
                                    {{ __('remaining') }}
                                </span>
                            </div>
                            @if ($job->highlight && $job->highlight_until && $setting->highlight_job_days > 0 && isFuture($job->highlight_until))
                                <div class="body-font-4 text-gray-600 pt-2">
                                    <span class="info-tools rt-mr-8">
                                        {{ __('highlight') }} {{ __('duration') }}:
                                    </span>
                                    <span class="info-tools">
                                        {{ now()->diffInDays($job->highlight_until) }}
                                        {{ __('days_remaining') }}
                                    </span>
                                </div>
                            @endif
                            @if ($job->featured && $job->featured_until && $setting->featured_job_days > 0 && isFuture($job->featured_until))
                                <div class="body-font-4 text-gray-600 pt-2">
                                    <span class="info-tools rt-mr-8">
                                        {{ __('featured') }} {{ __('duration') }}:
                                    </span>
                                    <span class="info-tools">
                                        {{ now()->diffInDays($job->featured_until) }}
                                        {{ __('days_remaining') }}
                                    </span>
                                </div>
                            @endif

                        </div>

                        <div class="applicant-count d-flex">
                            <span class>Applicants {{ $job->applied_jobs_count }}</span>
                            {{-- <p>Applicants</p> --}}
                        </div>

                    </div>

                    <div class="actions">
                        <a href="{{ route('agency.job.application', ['job' => $job->id]) }}" class="action">
                            <i class="fas fa-file-alt fa-1x"></i> <!-- Draft Icon -->
                            <span>Applications</span>
                        </a>
                        {{-- <a href="#pending" class="action">
                <i class="fas fa-clock fa-1x"></i> <!-- Pending Icon -->
                <span>Pending</span>
            </a> --}}
                        @if ($job->status == 'active')
                            <div class="action">
                                <i class="ph-check-circle f-size-10"></i>
                                {{ __('active') }}
                            </div>
                        @elseif ($job->status == 'pending')
                            <div class="action">
                                <i class="ph-hourglass f-size-10 "></i>
                                {{ __('pending') }}
                            </div>
                        @else
                            <div class="action">
                                <i class="ph-x-circle f-size-10"></i>
                                {{ __('job_expire') }}
                            </div>
                        @endif
                        <a href="{{ route('agency.job.edit', $job->slug) }}" class="action">
                            <i class="fas fa-edit fa-1x"></i> <!-- Edit Icon -->
                            <span>Edit</span>
                        </a>
                        <a href="{{ route('agency.promote', $job->slug) }}" class="action">
                            <i class="fas fa-filter fa-1x"></i> <!-- Filter Icon -->
                            <span>promote</span>
                        </a>
                        <a href="{{ route('website.job.details', $job->slug) }}" class="action">
                            <i class="fas fa-info-circle fa-1x"></i> <!-- Details Icon -->
                            <span>Details</span>
                        </a>
                    </div>
                @endforeach
            @endif

        </div>

    </div>



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dynamic Data from PHP
        const dates = @json($chartDates); // Example: ['2024-11-01', '2024-11-02']
        const counts = @json($chartCounts); // Example: [5, 10]

        // Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dates, // Dynamic dates
                datasets: [{
                    label: 'Daily Applications',
                    data: counts, // Dynamic counts
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

@endsection
@section('css')
    <style>
        /* General Card Styling */
        .profile-card {
            margin-top: 10px;
            /* border: 1px solid #ddd;
                        border-radius: 8px; */
            padding: 0px;
            /* background-color: #fff; */
            max-width: 100%;
            /* box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); */
        }

        /* Header Layout */
        .profile-header {
            display: flex;
            /* align-items: center; */
            /* gap: 10px; */
            /* flex-wrap: wrap; */
            text-align: left;
        }

        .profile-image {
            margin: 0px !important;
        }

        .profile-image img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
        }

        .profile-details {
            flex: 1;
            min-width: 150px;
        }

        .profile-details h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .profile-details p {
            margin: 2px 0;
            font-size: 11px;
            color: #555;
        }

        /* Verified Badge */
        .verification {

            font-size: 11px;
            color: #007bff;
            font-weight: bold;
        }

        /* Buttons Layout */
        .profile-actions {
            display: flex;
            gap: 8px;
            font-size: 11px;
            margin-top: 16px;
            /* flex-direction: column; Stacked on small screens */
            flex-direction: row;
            justify-content: space-between;
        }

        .profile-actions button {
            flex: 1;
            padding: 6px;
            border: none;
            border-radius: 4px;
            font-size: 8px;
            cursor: pointer;
            text-align: center;
        }

        /* Button Colors */
        .unviewed-cv {
            background-color: #6cbb3c;
            color: #fff;
        }

        .cv-since-login,
        .new-cvs-today {
            background-color: #ddd;
            color: #333;
        }

        /* Responsive Adjustments */
        @media (min-width: 600px) {
            .profile-header {
                flex-direction: row;
            }

            .profile-actions {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .job-listing {
            border-bottom: 2px solid black
        }

        .job-header {
            margin-top: 13px;
            color: black;
        }

        .job-header p {
            color: #555;
            font-size: 12px
        }

        .job-header-title {
            color: black;
            text-decoration: none;
            transition: transform 0.3s ease, background-color 0.3s ease;

        }

        .job-header-title:hover {
            transform: scale(1.1);
            /* Expands the action on hover */
            background-color: #f0f0f0;
            /* Optional: light background on hover */
            color: black;
        }


        .applicant-count {
            background-color: #4576d3;
            color: white;
            padding: 0;
            /* No padding to ensure text is centered properly */
            border-radius: 50%;
            /* Ensures it's circular */
            text-align: center;
            font-size: 7px;
            /* Adjusted font size for better fit */
            width: 60px;
            /* Fixed width */
            height: 60px;
            /* Fixed height */
            display: flex;
            justify-content: center;
            /* Center the content horizontally */
            align-items: center;
            /* Center the content vertically */
        }



        .header {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .action {
            color: black;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .action:hover {
            transform: scale(1.1);
            /* Expands the action on hover */
            background-color: #f0f0f0;
            /* Optional: light background on hover */
            color: black;
        }


        .icon-document,
        .icon-clock,
        .icon-edit,
        .icon-filter,
        .icon-details {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }

        .mblscreen {
            display: block;
        }

        .laptopscreen {
            display: none;
        }

        /* Tablet and larger screens (768px and above): Show .laptopscreen and hide .mblscreen */
        @media (min-width: 769px) {
            .mblscreen {
                display: none;
            }

            .laptopscreen {
                display: block;
            }
        }
    </style>
@endsection
