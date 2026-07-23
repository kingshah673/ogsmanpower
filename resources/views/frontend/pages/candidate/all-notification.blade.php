@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('all_notification') }}
@endsection

@section('main')
    <div class="dashboard-wrapper seeker-settings-page">
        <div class="container">
            <div class="dashboard-right">

                <x-website.candidate.seeker-page-header
                    :title="__('all_notifications')"
                    :subtitle="__('Your recent account and job notifications.')"
                />

                <div class="glass-card"><div class="glass-card-body">
                        <div class="db-job-card-table">
                            @if ($notifications->count() > 0)
                                @foreach ($notifications as $noti)
                                    @php
                                        $data = is_array($noti->data) ? $noti->data : [];
                                        $title = $data['title2'] ?? $data['title'] ?? $data['subject'] ?? __('notification');
                                        $url = $data['url2'] ?? $data['url'] ?? route('candidate.dashboard');
                                    @endphp
                                    <div class="card jobcardStyle1 rt-mb-12 {{ $noti->read_at ? '' : 'border-primary' }}">
                                        <div class="card-body">
                                            <a href="javascript:void(0)"
                                               onclick="readSingleNotification(@js($url), @js($noti->id))"
                                               class="d-block text-decoration-none">
                                                <div class="rt-single-icon-box">
                                                    <div class="icon-thumb rt-mr-16 text-primary-500">
                                                        <svg width="36" height="36" fill="none" stroke="currentColor"
                                                             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="iconbox-content">
                                                        <div class="body-font-3 text-gray-700 rt-mb-4">{{ $title }}</div>
                                                        <div class="body-font-4 text-gray-400">{{ $noti->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </div>
                                            </a>
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
