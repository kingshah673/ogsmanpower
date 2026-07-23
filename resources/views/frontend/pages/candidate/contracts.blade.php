@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('Contracts') }}
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/candidate-settings-classic.css') }}?v={{ @filemtime(public_path('css/candidate-settings-classic.css')) ?: '1' }}">
@endsection

@section('main')
<div class="dashboard-wrapper seeker-module-page">
    <div class="container">
        <div class="dashboard-right">

            <x-website.candidate.seeker-page-header
                :title="__('Contracts')"
                :subtitle="__('Review and respond to employment contracts sent to you.')"
            />

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($contracts->count())
                <div class="row">
                    @foreach($contracts as $contract)
                        <div class="col-md-6 mb-3">
                            <div class="glass-card h-100">
                                <div class="glass-card-body">
                                    <h3 class="mb-2">{{ $contract->contract_title }}</h3>
                                    <p class="text-muted small mb-3">{{ $contract->contract_details }}</p>

                                    <div class="pv-field-grid mb-3">
                                        <div class="pv-readonly-field">
                                            <label class="pv-readonly-label">{{ __('Salary') }}</label>
                                            <div class="pv-readonly-value">{{ $contract->salary ?: '—' }}</div>
                                        </div>
                                        <div class="pv-readonly-field">
                                            <label class="pv-readonly-label">{{ __('Location') }}</label>
                                            <div class="pv-readonly-value">{{ $contract->location ?: '—' }}</div>
                                        </div>
                                        <div class="pv-readonly-field">
                                            <label class="pv-readonly-label">{{ __('Status') }}</label>
                                            <div class="pv-readonly-value">{{ ucfirst($contract->status) }}</div>
                                        </div>
                                    </div>

                                    @if($contract->status == 'sent')
                                        <form method="POST" action="{{ route('candidate.contract.accept', $contract->id) }}" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-primary w-100">{{ __('Accept Contract') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('candidate.contract.reject', $contract->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger w-100">{{ __('Reject Contract') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="glass-card">
                    <div class="glass-card-body text-center py-5">
                        <i class="ph-file-doc fa-2x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">{{ __('No contracts have been sent to you yet.') }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
