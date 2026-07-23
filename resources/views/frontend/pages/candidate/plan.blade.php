{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')
@section('title', __('dashboard'))
<style>
    .card {
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: scale(1.05);
    }

    .btn-primary {
        background-color: #28a745;
        border-color: #28a745;
    }
</style>
@section('main')
<div class="dashboard-wrapper seeker-settings-page">
<div class="container">
<div class="dashboard-right">

<x-website.candidate.seeker-page-header
    :title="__('plans')"
    :subtitle="__('Feature your profile by subscribing to a plan.')"
/>

<div class="glass-card"><div class="glass-card-body">

@if($plan)
 @if ($plan_Subscription && $plan_Subscription->candidate && $plan_Subscription->candidate->is_candidate_featured == '1')
 <div class="text-center my-5">
     <h1>Plan Subscribed</h1>
 </div>
@elseif ($plan_Subscription && $plan_Subscription->payment_type == 'manual' && optional($plan_Subscription->candidate)->is_candidate_featured == '0')
 <div class="text-center my-5">
     <h1>Wait For Admin Approval</h1>
 </div>
@else
 <div class="container">
     <div class="text-center my-5">
         <h1>Subscribe to a Plan</h1>
         <p class="lead">Feature your profile by subscribing to one of our plans.</p>
     </div>

     <div class="row justify-content-center">
         <div class="col-md-4">
             <div class="card shadow-sm">
                 <div class="card-body text-center">
                     <h5 class="card-title">{{ $plan->name }}</h5>
                     <p class="card-text">Price: {{ $plan->price }}</p>
                     <p class="card-text">Duration: {{ $plan->duration }} days</p>
                     <form method="post" action="{{ route('website.candidate.plan.details') }}">
                         @csrf
                         <input type="hidden" name="price" value="{{ $plan->price }}">
                         <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                         <input type="hidden" name="candidate_id" value="{{ auth()->user()->candidate->id ?? '' }}">
                         <button type="submit" class="btn btn-primary">Get Started</button>
                     </form>
                 </div>
             </div>
         </div>
     </div>
 </div>
@endif
@else
 <div class="text-center py-5">
     <p class="text-muted mb-0">{{ __('No subscription plans are available at the moment.') }}</p>
 </div>
@endif

</div></div>

</div>
</div>
</div>

@endsection
