{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')


@section('title')
    {{ __('job_alert') }}
@endsection

@section('main')
    <div class="dashboard-wrapper seeker-settings-page">
        <div class="container">
            <div class="dashboard-right">

                <x-website.candidate.seeker-page-header
                    :title="__('job_alert')"
                    :subtitle="__('Notifications when new jobs match your alerts.')"
                />

                <div class="glass-card"><div class="glass-card-body">
                <div class="db-job-card-table">
                            @if ($notifications->count() > 0)
                                @foreach ($notifications as $noti)
                                    <div class="card jobcardStyle1 rt-mb-12">
                                        <div class="card-body">
                                            <div class="rt-single-icon-box">
                                                <div class="iconbox-content">
                                                    <a href="{{ $noti->data['url'] }}">
                                                        <li class="d-block">
                                                            <div class="rt-single-icon-box">
                                                                <div class="icon-thumb rt-mr-16 text-primary-500">
                                                                    <svg width="40" height="40" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24"
                                                                        xmlns="http://www.w3.org/2000/svg">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                                        </path>
                                                                    </svg>
                                                                </div>
                                                                <div class="iconbox-content">
                                                                    <div class="body-font-4 text-gray-700 rt-mb-4">
                                                                        {{ $noti->data['title'] }}
                                                                    </div>
                                                                    @if(isset($noti->data['company_name']) && isset($noti->data['role_name']))
                                                                        <div class="body-font-2 text-gray-700 rt-mb-4">
                                                                            at <strong>{{ $noti->data['company_name'] }}</strong> as <strong>{{ $noti->data['role_name'] }}</strong>
                                                                        </div>
                                                                    @endif
                                                                    <div class="body-font-4 text-gray-400">
                                                                        {{ $noti->created_at->diffForHumans() }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <x-not-found message="{{ __('no_data_found') }}" />
                            @endif
                        </div>
                        <div class="rt-pt-12">
                            @if ($notifications->total() > $notifications->count())
                                <nav>
                                    {{ $notifications->links('vendor.pagination.frontend') }}
                                </nav>
                            @endif
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
@endsection
