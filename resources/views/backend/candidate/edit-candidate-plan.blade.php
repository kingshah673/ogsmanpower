@extends('backend.settings.setting-layout')

@section('title')
    {{ __('Plan') }}
@endsection
@section('content')
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h5 class="text-center">{{ $plan ? __('Edit Subscription Plan') : __('Add a Subscription Plan') }}</h5>
                <p class="text-center">
                    {{ $plan ? __('Update the seeker subscription plan details below.') : __('No plan exists yet. Enter details to create the seeker subscription plan.') }}
                </p>
                <form action="{{ route('storeOrUpdatePlan') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="planName" class="form-label">Plan Name</label>
                        <input type="text" class="form-control @error('planName') is-invalid @enderror" id="planName"
                            name="planName" value="{{ old('planName', $plan->name ?? '') }}"
                            placeholder="Enter plan name" required>
                        @error('planName')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (USD)</label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('price') is-invalid @enderror" id="price" name="price"
                            value="{{ old('price', $plan->price ?? '') }}" placeholder="Enter price" required>
                        @error('price')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration (Days)</label>
                        <input type="number" min="1"
                            class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration"
                            value="{{ old('duration', $plan->duration ?? '') }}"
                            placeholder="Enter duration in days" required>
                        @error('duration')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        {{ $plan ? __('Update Plan') : __('Create Plan') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
