@extends('frontend.layouts.app')

@php
$data = metaData('home');
@endphp

@section('title',$data->title ?? 'Home')
@section('description',$data->description ?? '')
@section('og:image',asset($data->image ?? ''))

@section('css')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">

<style>

.section-space{
padding:80px 0;
}

.section-title{
font-size:30px;
font-weight:700;
margin-bottom:40px;
}

.card-ui{
background:#fff;
border-radius:16px;
padding:25px;
box-shadow:0 10px 25px rgba(0,0,0,.05);
transition:.3s;
}

.card-ui:hover{
transform:translateY(-6px);
box-shadow:0 20px 40px rgba(0,0,0,.08);
}

.job-badge{
background:#eef2ff;
color:#4f46e5;
padding:4px 10px;
border-radius:20px;
font-size:12px;
}

.company-logo{
width:50px;
height:50px;
border-radius:10px;
object-fit:cover;
}

.banner{
padding:70px;
border-radius:20px;
text-align:center;
color:#fff;
background:linear-gradient(135deg,#4f46e5,#6366f1);
}

.banner-dark{
background:linear-gradient(135deg,#111827,#1f2937);
}

.stat-box{
background:#fff;
padding:30px;
border-radius:16px;
text-align:center;
box-shadow:0 15px 35px rgba(0,0,0,.05);
}

.stat-card{
background:#fff;
padding:35px 20px;
border-radius:16px;
text-align:center;
box-shadow:0 15px 35px rgba(0,0,0,.06);
transition:.3s;
}

.stat-card:hover{
transform:translateY(-6px);
box-shadow:0 20px 50px rgba(0,0,0,.08);
}

.stat-card i{
font-size:26px;
color:#4f46e5;
margin-bottom:10px;
}

.stat-card h3{
font-size:32px;
font-weight:700;
margin-bottom:5px;
}

.stat-card p{
color:#6b7280;
margin:0;
font-size:14px;
}

.hire-banner{
position:relative;
padding:80px 60px;
border-radius:20px;
overflow:hidden;
background:url('/images/banner/hire-banner.jpg') center/cover no-repeat;
color:#fff;
}

.hire-banner::before{
content:"";
position:absolute;
top:0;
left:0;
width:100%;
height:100%;

}

.hire-banner .row{
position:relative;
z-index:2;
}

.hire-title{
font-size:38px;
font-weight:700;
margin-bottom:15px;
color:white;
}

.hire-text{
font-size:16px;
line-height:1.8;
margin-bottom:25px;
opacity:.95;
}

.hire-buttons .btn{
padding:12px 26px;
font-weight:600;
border-radius:8px;
}

.banner-icon{
font-size:120px;
opacity:.2;
}

.cv-banner{
position:relative;
padding:80px 60px;
border-radius:20px;
overflow:hidden;
background:url('/images/banner/cv-banner.jpg') center/cover no-repeat;
color:#fff;
}

.cv-banner::before{
content:"";
position:absolute;
top:0;
left:0;
width:100%;
height:100%;

}

.cv-banner .row{
position:relative;
z-index:2;
}

.cv-title{
font-size:36px;
font-weight:700;
margin-bottom:15px;
color:white;
}

.cv-text{
font-size:16px;
line-height:1.8;
opacity:.95;
}

.cv-icon{
font-size:120px;
opacity:.2;
}
.company-logo {
    width: auto;
    height: 45px;          /* 🔥 control size here */
    max-width: 60px;       /* prevent large logos */
    object-fit: contain;
    display: block;
}
.candidate-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}


</style>

@endsection



@section('main')


{{-- FEATURED CANDIDATES --}}

<div class="container section-space">

<h2 class="section-title">Featured Candidates</h2>

<div class="row">

@foreach($featured_candidates ?? [] as $candidate)

<div class="col-lg-3 mb-4">

<a href="{{ optional($candidate->user)->username ? route('website.candidate.details',optional($candidate->user)->username) : '#' }}">

<div class="card-ui text-center">

<img src="{{ Str::startsWith($candidate->photo, 'http') 
    ? $candidate->photo 
    : asset('storage/'.$candidate->photo) }}"
class="candidate-img mb-3">

<h6>{{ optional($candidate->user)->name }}</h6>

<p class="text-muted small">
@if($candidate->is_candidate_featured == 1)
    <p class="text-muted small">
        {{ optional($candidate->profession)->name ?? 'Professional' }}
    </p>
@endif
</p>

</div>

</a>

</div>

@endforeach

</div>

</div>

</div>



{{-- CANDIDATES BY COUNTRY --}}

<div class="container section-space">

<h2 class="section-title">Candidates By Country</h2>

@php
use App\Models\Candidate;

$allowedSortNames = [
    'PK', // Pakistan
    'IN', // India
    'PH', // Philippines
    'BD', // Bangladesh
    'NP', // Nepal
    'LK', // Sri Lanka
    'EG', // Egypt
    'ZA'  // South Africa
];

// Filter + sort countries
$countries = collect($candidate_countries)
    ->whereIn('sortname', $allowedSortNames)
    ->sortBy(function($country) use ($allowedSortNames) {
        return array_search($country->sortname, $allowedSortNames);
    })
    ->values();
@endphp

<div class="row">

@foreach($countries as $key => $country)

@php
// Dynamic candidate count (based on country name)
$count = Candidate::where('country', $country->name)->count();
@endphp

<div class="col-lg-3 col-md-4 col-6 mb-3 {{ $key >= 18 ? 'extra-country d-none' : '' }}">

<a href="{{ route('website.candidates.by.country', $country->sortname) }}">

<div class="card-ui text-center">

{{-- FLAG --}}
<i class="flag-icon flag-icon-{{ strtolower($country->sortname) }} mb-2" style="font-size: 46px;"></i>

{{-- COUNTRY NAME --}}
<div>{{ $country->name }}</div>

{{-- CANDIDATE COUNT --}}
<div class="text-muted small">
    {{ $count }} Candidates
</div>

</div>

</a>

</div>

@endforeach

</div>

@if($countries->count() > 16)
<div class="text-center mt-4">
    <button class="btn btn-primary" onclick="showCountries()">+ More</button>
</div>
@endif

</div>



{{-- COMPANY BANNER WITH IMAGE --}}

<section class="section-space">

<div class="container">

<div class="hire-banner">

<div class="row align-items-center">

<div class="col-lg-7">

<h2 class="hire-title">
Hire The Best Talent For Your Company
</h2>

<p class="hire-text">
Access thousands of skilled professionals ready to contribute to your business.
Find qualified candidates faster and build a stronger workforce.
</p>

<div class="hire-buttons">

<a href="{{ route('website.candidate') }}" class="btn btn-light btn-lg me-3">
Browse Candidates
</a>

<a href="{{ route('register','company') }}" class="btn btn-outline-light btn-lg">
Post a Job
</a>

</div>

</div>

<div class="col-lg-5 text-end">

<i class="fas fa-users banner-icon"></i>

</div>

</div>

</div>

</div>

</section>





{{-- FEATURED JOBS --}}

<div class="container section-space">

<h2 class="section-title">Featured Jobs</h2>

<div class="row">

@foreach($featured_jobs ?? [] as $job)

<div class="col-lg-4 mb-4">

<a href="{{ $job->slug ? route('website.job.details',$job->slug) : '#' }}">

<div class="card-ui">

<div class="d-flex align-items-center mb-3">

<img src="{{ asset(optional($job->company)->logo ?? 'images/company.png') }}"
     class="company-logo me-3"
     alt="Company Logo">

<div>

<h6 class="mb-0">{{ $job->title }}</h6>

<small class="text-muted">
{{ optional(optional($job->company)->user)->name }}
</small>

</div>

</div>

<span class="job-badge">
{{ optional($job->job_type)->name ?? 'Full Time' }}
</span>

<p class="small text-muted mt-2">
<i class="fas fa-map-marker-alt"></i>
{{ $job->country }}
</p>

<p class="small text-muted mb-0">
<i class="fas fa-money-bill-wave"></i>
{{ __('salary') }}:
@if ($job->salary_mode == 'range')
{{ currencyAmountShort($job->min_salary,1) }}
-
{{ currencyAmountShort($job->max_salary,1) }}
{{ $job->currency }}
@else
{{ $job->custom_salary }}
@endif
</p>

</div>

</a>

</div>

@endforeach

</div>

</div>



{{-- JOBS BY COUNTRY --}}

<div class="container section-space">

<h2 class="section-title">Jobs By Country</h2>

@php
use App\Models\Job;

// Allowed countries (by sortname)
$allowedSortNames = [
    'SA', // Saudi Arabia
    'AE', // Dubai (UAE)
    'QA', // Qatar
    'OM', // Oman
    'BH', // Bahrain
    'TR', // Turkey
    'MY', // Malaysia
    'RO'  // Romania
];

// Filter + sort
$countries = collect($job_countries)
    ->whereIn('sortname', $allowedSortNames)
    ->sortBy(function($country) use ($allowedSortNames) {
        return array_search($country->sortname, $allowedSortNames);
    })
    ->values();
@endphp

<div class="row">

@foreach($countries as $key => $country)

@php
// Dynamic job count (based on country name)
$count = Job::where('country', $country->name)->count();
@endphp

<div class="col-lg-3 col-md-4 col-6 mb-3 {{ $key >= 16 ? 'extra-job-country d-none' : '' }}">

<a href="{{ route('website.jobs.by.country', $country->sortname) }}">

<div class="card-ui text-center">

{{-- FLAG --}}
<i class="flag-icon flag-icon-{{ strtolower($country->sortname) }} mb-2" style="font-size: 46px;"></i>

{{-- COUNTRY NAME --}}
<div>{{ $country->name }}</div>

{{-- JOB COUNT --}}
<div class="text-muted small">
    {{ $count }} Jobs
</div>

</div>

</a>

</div>

@endforeach

</div>

@if($countries->count() > 16)
<div class="text-center mt-4">
    <button class="btn btn-primary" onclick="showJobCountries()">+ More</button>
</div>
@endif

</div>



{{-- CV BUILDER BANNER PROFESSIONAL --}}

<section class="section-space">

<div class="container">

<div class="cv-banner">

<div class="row align-items-center">

<div class="col-lg-7">

<h2 class="cv-title">
Create Your Professional CV
</h2>

<p class="cv-text">
Build a powerful resume that highlights your skills and experience.
Stand out to employers and increase your chances of getting hired.
</p>

<a href="{{ route('register','candidate') }}"
class="btn btn-light btn-lg mt-3">
Build Your CV Now
</a>

</div>

<div class="col-lg-5 text-end">

<i class="fas fa-file-alt cv-icon"></i>

</div>

</div>

</div>

</div>

</section>



{{-- JOBS BY CATEGORY --}}

<div class="container section-space">

<h2 class="section-title">Jobs By Category</h2>

<div class="row">

@foreach($popular_categories ?? [] as $key => $cat)

<div class="col-lg-3 col-md-4 col-6 mb-3 {{ $key >= 16 ? 'extra-category d-none' : '' }}">

<a href="{{ $cat->slug ? route('website.job.category.slug',$cat->slug) : '#' }}">

<div class="card-ui text-center">

{{-- ICON / IMAGE --}}
@if(!empty($cat->icon))
    <img src="{{ asset($cat->icon) }}" width="40" class="mb-2">
@else
    <i class="fa fa-briefcase fa-2x text-primary mb-2"></i>
@endif

{{-- CATEGORY NAME --}}
<h6>
    {{ is_numeric($cat->name) ? 'Category '.$cat->name : $cat->name }}
</h6>

{{-- JOB COUNT --}}
<small>{{ $cat->jobs_count ?? 0 }} Jobs</small>

</div>

</a>

</div>

@endforeach

</div>

@if(($popular_categories ?? collect())->count() > 16)

<div class="text-center mt-4">
<button class="btn btn-primary" onclick="showCategories()">+ More</button>
</div>

@endif

</div>



{{-- ABOUT SECTION PROFESSIONAL --}}

<section class="section-space bg-light" style="">

<div class="container">

<div class="row align-items-center">

{{-- LEFT SIDE CONTENT --}}

<div class="col-lg-6">

<span class="text-primary fw-bold mb-2 d-block">
ABOUT OUR PLATFORM
</span>



<p class="text-muted mb-4" style="font-size:16px; line-height:1.8;">
Our platform helps companies discover top talent and enables candidates to
find the best career opportunities worldwide. We simplify hiring by
connecting employers and job seekers through a modern and efficient job
portal.
</p>

<ul class="list-unstyled mb-4">

<li class="mb-2">
<i class="fas fa-check-circle text-primary me-2"></i>
Thousands of verified companies
</li>

<li class="mb-2">
<i class="fas fa-check-circle text-primary me-2"></i>
Smart job matching system
</li>

<li class="mb-2">
<i class="fas fa-check-circle text-primary me-2"></i>
Global opportunities for professionals
</li>

</ul>

<a href="{{ route('website.job') }}"
class="btn btn-primary px-4 py-2">
Find Jobs Now
</a>

</div>


{{-- RIGHT SIDE STATS --}}

<div class="col-lg-6">

<div class="row g-4">

<div class="col-6">

<div class="stat-card">

<i class="fas fa-briefcase"></i>

<h3 class="counter">{{ $newjobs ?? 0 }}</h3>

<p>Jobs Posted</p>

</div>

</div>


<div class="col-6">

<div class="stat-card">

<i class="fas fa-building"></i>

<h3 class="counter">{{ $companies ?? 0 }}</h3>

<p>Companies</p>

</div>

</div>


<div class="col-6">

<div class="stat-card">

<i class="fas fa-users"></i>

<h3 class="counter">{{ $Candidates ?? 0 }}</h3>

<p>Candidates</p>

</div>

</div>


<div class="col-6">

<div class="stat-card">

<i class="fas fa-check-circle"></i>

<h3 class="counter">1200</h3>

<p>Jobs Filled</p>

</div>

</div>

</div>

</div>

</div>

</div>

</div>
</section>
@endsection



@section('js')

<script>

function showCountries(){
document.querySelectorAll('.extra-country').forEach(el => el.classList.remove('d-none'))
}

function showJobCountries(){
document.querySelectorAll('.extra-job-country').forEach(el => el.classList.remove('d-none'))
}

function showCategories(){
document.querySelectorAll('.extra-category').forEach(el => el.classList.remove('d-none'))
}

</script>

<script>

document.querySelectorAll('.counter').forEach(counter => {

let target = +counter.innerText;

let count = 0;

let speed = target / 100;

let update = () => {

count += speed;

if(count < target){

counter.innerText = Math.floor(count);

requestAnimationFrame(update);

}else{

counter.innerText = target;

}

}

update();

});

</script>

@endsection

