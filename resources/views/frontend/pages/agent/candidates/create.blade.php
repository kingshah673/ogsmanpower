@extends('components.website.agent.new-sidebar')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>

/* ========================================
   FULL PAGE PREMIUM FORM CSS
======================================== */

body{
    background:#f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

/* Container */
.container{
    max-width: 1400px;
}

/* Cards */
.card{
    border:none;
    border-radius:16px;
    box-shadow:0 8px 24px rgba(0,0,0,0.06);
    overflow:hidden;
    margin-bottom:25px;
}

.card-header{
    background:#ffffff;
    padding:18px 25px;
    font-size:20px;
    font-weight:700;
    color:#1e293b;
    border-bottom:1px solid #eef2f7;
}

.card-body{
    padding:25px;
}

/* Labels */
label{
    font-size:14px;
    font-weight:600;
    margin-bottom:7px;
    color:#334155;
    display:block;
}

/* Inputs */
.form-control,
.form-select,
select,
textarea{
    height:48px;
    border:1px solid #dbe3ec;
    border-radius:10px;
    padding:10px 14px;
    font-size:14px;
    box-shadow:none !important;
    transition:0.3s;
    width:100%;
    background:#fff;
}

textarea{
    height:auto;
    min-height:130px;
}

.form-control:focus,
select:focus,
textarea:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.10) !important;
}

/* Buttons */
.btn{
    height:48px;
    border-radius:12px;
    font-weight:600;
    padding:0 24px;
}

.btn-success{
    background:#16a34a;
    border:none;
}

.btn-success:hover{
    background:#15803d;
}

.btn-primary{
    background:#2563eb;
    border:none;
}

.btn-primary:hover{
    background:#1d4ed8;
}

/* Upload Box */
.upload-box{
    border:2px dashed #d7e1ee;
    padding:25px 15px;
    border-radius:14px;
    background:#fafcff;
    cursor:pointer;
    transition:.3s;
    position:relative;
}

.upload-box:hover{
    border-color:#2563eb;
    background:#f4f8ff;
}

.upload-box i{
    font-size:34px;
    margin-bottom:10px;
}

.upload-box p{
    margin:0;
    font-weight:600;
}

.upload-box small{
    color:#64748b;
}

.file-input{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
}

/* Section spacing */
.row.g-3 > div{
    margin-bottom:10px;
}

/* Save Footer */
.page-save-btn{
    position:sticky;
    bottom:20px;
    z-index:999;
    text-align:right;
}

/* ========================================
   FLATPICKR CUSTOM DESIGN
======================================== */

.flatpickr-calendar{
    border:none !important;
    border-radius:16px !important;
    box-shadow:0 12px 35px rgba(0,0,0,0.15) !important;
    padding:10px;
    width:320px;
}

.flatpickr-months{
    margin-bottom:10px;
}

.flatpickr-current-month{
    display:flex !important;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding-top:8px;
}

.flatpickr-current-month .flatpickr-monthDropdown-months{
    border:none !important;
    font-size:15px;
    font-weight:700;
    color:#111827;
    background:transparent;
}

.flatpickr-current-month .numInputWrapper{
    width:75px !important;
    display:inline-block !important;
}

.flatpickr-current-month input.cur-year{
    font-size:15px !important;
    font-weight:700 !important;
    color:#111827 !important;
    visibility:visible !important;
    opacity:1 !important;
    background:#f8fafc !important;
    border-radius:8px;
    border:1px solid #dbe3ec;
    height:34px;
}

.flatpickr-weekdays{
    margin-top:8px;
}

span.flatpickr-weekday{
    color:#64748b;
    font-weight:600;
}

.flatpickr-day{
    border-radius:10px !important;
    height:40px;
    line-height:40px;
    font-weight:600;
}

.flatpickr-day.today{
    border:1px solid #2563eb;
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange{
    background:#2563eb !important;
    border-color:#2563eb !important;
    color:#fff !important;
}

.flatpickr-day:hover{
    background:#eff6ff;
    color:#2563eb;
}

/* Arrows */
.flatpickr-prev-month,
.flatpickr-next-month{
    padding-top:8px !important;
}

.flatpickr-prev-month svg,
.flatpickr-next-month svg{
    width:16px;
    height:16px;
}
.form-label{
    margin-bottom:8px;
    color:#334155;
}

.form-control{
    height:48px;
    border-radius:10px;
    border:1px solid #dbe3ec;
    padding:10px 14px;
}

.form-control:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.10);
}

.card{
    border-radius:16px;
}
.search-wrapper{
    position:relative;
}

#search_address{
    height:48px;
    border-radius:12px;
}

#address_results{
    position:absolute;
    top:100%;
    left:0;
    right:0;
    background:#fff;
    border:1px solid #ddd;
    border-radius:12px;
    max-height:320px;
    overflow-y:auto;
    z-index:9999;
    display:none;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.result-item{
    padding:12px 15px;
    cursor:pointer;
    border-bottom:1px solid #f1f1f1;
    font-size:14px;
}

.result-item:hover{
    background:#f8fafc;
}

#map{
    height:400px;
    border-radius:14px;
}


/* ========================================
   RESPONSIVE
======================================== */

@media(max-width:768px){

.card-header{
    font-size:18px;
}

.card-body{
    padding:18px;
}

.form-control,
select,
textarea{
    height:45px;
}

.flatpickr-calendar{
    width:100%;
}


}

</style>
@section('main')

<div class="container py-4">

<form action="{{ route('agent.candidates.store') }}" method="POST" enctype="multipart/form-data">
@csrf

{{-- HEADER --}}
<div class="card border-0 shadow-lg rounded-4 mb-4">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1">Add Candidate Profile</h2>
                <p class="text-muted mb-0">Premium Smart Registration Form</p>
            </div>

            <button class="btn btn-success px-4 py-2 rounded-pill">
                <i class="fas fa-save me-2"></i> Save Candidate
            </button>
        </div>

    </div>
</div>

{{-- PERSONAL INFORMATION --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Personal Information</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-4">
<label>First Name</label>
<input type="text" name="first_name" class="form-control">
</div>

<div class="col-md-4">
<label>Last Name</label>
<input type="text" name="last_name" class="form-control">
</div>

<div class="col-md-4">
<label>Professional Title</label>
<input type="text" name="title" class="form-control">
</div>

<div class="col-md-4">
<label>Gender</label>
<select name="gender" class="form-control">
<option value="">Select</option>
<option value="male">Male</option>
<option value="female">Female</option>
</select>
</div>

<div class="col-md-4">
<label>Date of Birth</label>
<input type="text" name="birth_date" class="form-control calendar">
</div>

<div class="col-md-4">
<label>Marital Status</label>
<select name="marital_status" class="form-control">
<option value="">Select</option>
<option value="single">Single</option>
<option value="married">Married</option>
</select>
</div>

<div class="col-md-4">
<label>Experience Level</label>
<select name="experience_id" class="form-control">
<option value="">Select</option>
@foreach($experiences as $item)
<option value="{{ $item->id }}">{{ $item->name }}</option>
@endforeach
</select>
</div>

<div class="col-md-4">
<label>Education Level</label>
<select name="education_id" class="form-control">
<option value="">Select</option>
@foreach($educations as $item)
<option value="{{ $item->id }}">{{ $item->name }}</option>
@endforeach
</select>
</div>

<div class="col-md-4">
<label>Profession</label>
<select name="profession_id" class="form-control">
<option value="">Select</option>
@foreach($professions as $item)
<option value="{{ $item->id }}">{{ $item->name }}</option>
@endforeach
</select>
</div>

</div>
</div>
</div>

{{-- CONTACT --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Contact Information</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-3">
<label>Phone</label>
<input type="tel" name="phone" class="form-control intl-phone-input">
</div>

<div class="col-md-3">
<label>Secondary Phone</label>
<input type="text" name="secondary_phone" class="form-control">
</div>

<div class="col-md-3">
<label>WhatsApp</label>
<input type="tel" name="whatsapp_number" class="form-control intl-phone-input">
</div>

<div class="col-md-3">
<label>Email</label>
<input type="email" name="email" class="form-control">
</div>

</div>
</div>
</div>

{{-- PASSPORT --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Passport Details</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-3">
<label>Passport Number</label>
<input type="text" name="passport_number" class="form-control">
</div>

<div class="col-md-3">
<label>Issue Date</label>
<input type="text" name="passport_issue_date" class="form-control calendar">

</div>

<div class="col-md-3">
<label>Expiry Date</label>
<input type="text" name="passport_expiry_date" class="form-control calendar">
</div>

<div class="col-md-3">
<label>Place Of Issue</label>
<input type="text" name="place_of_issue" class="form-control">
</div>

</div>
</div>
</div>

{{-- CNIC --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">CNIC</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-6">
<label>CNIC Number</label>
<input type="text" name="cnic_number" class="form-control">
</div>

<div class="col-md-6">
<label>Upload CNIC</label>
<input type="file" name="cnic_document" class="form-control">
</div>

</div>
</div>
</div>

{{-- LOCATION --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">
Address
</div>

<div class="card-body p-4">

<div class="row g-3">

<!-- Search -->
<div class="col-md-12">
<label>Search Address</label>

<div class="search-wrapper">
<input type="text"
       id="search_address"
       class="form-control"
       placeholder="Search city, street, country">

<div id="address_results"></div>
</div>
</div>

<!-- Full Address -->
<div class="col-md-12">
<label>Full Address</label>
<input type="text" name="address" id="full_address" class="form-control">
</div>

<!-- Country -->
<div class="col-md-4">
<label>Country</label>
<input type="text" name="country" id="country" class="form-control">
</div>

<!-- State -->
<div class="col-md-4">
<label>State</label>
<input type="text" name="region" id="state" class="form-control">
</div>

<!-- City -->
<div class="col-md-4">
<label>City</label>
<input type="text" name="district" id="city" class="form-control">
</div>

<input type="hidden" name="lat" id="lat">
<input type="hidden" name="lng" id="lng">

<!-- Map -->
<div class="col-md-12">
<div id="map"></div>
</div>

</div>
</div>
</div>

{{-- JOB PREFERENCES --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Job Preferences</div>

<div class="card-body p-4">

<div class="row g-3">

<!-- Preferred Job -->
<div class="col-md-4">
<label>Preferred Job</label>
<select name="preferred_job" class="form-control">
<option value="">Select Job</option>

@foreach($professions as $item)
<option value="{{ $item->id }}">
{{ $item->name }}
</option>
@endforeach

</select>
</div>

<!-- Expected Salary -->
<div class="col-md-4">
<label>Expected Salary</label>
<input type="text" name="expected_salary" class="form-control">
</div>



<!-- Availability -->
<div class="col-md-4">
<label>Status</label>
<select name="status" class="form-control">
<option value="available">Available</option>
<option value="not_available">Not Available</option>
<option value="available_in">Available In</option>
</select>
</div>

<!-- Available Date -->
<div class="col-md-4">
<label>Available In Date</label>
<input type="text" name="available_in" class="form-control calendar">
</div>

<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-header bg-white fw-bold fs-5">
        Preferred Location
    </div>

    <div class="card-body">

        @php
            session([
                'selectedCountryId' => null,
                'selectedStateId'   => null,
                'selectedCityId'    => null,
            ]);
        @endphp

        @livewire('country-state-city')

    </div>
</div>

{{-- SKILLS --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Skills</div>
<div class="card-body p-4">
<p> Press Ctrl to Select Multiple Skills</p>
<select name="skills[]" class="form-control" multiple>
@foreach($skills as $skill)
<option value="{{ $skill->id }}">{{ $skill->name }}</option>
@endforeach
</select>

</div>
</div>

{{-- LANGUAGES --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Languages</div>
<div class="card-body p-4">
<p> Press Ctrl to Select Multiple Languages</p>
<select name="languages[]" class="form-control" multiple>
@foreach($languages as $lang)
<option value="{{ $lang->id }}">{{ $lang->name }}</option>
@endforeach
</select>

</div>
</div>

{{-- DOCUMENTS --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Documents</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-4">
<label>Profile Photo</label>
<input type="file" name="photo" class="form-control">
</div>

<div class="col-md-4">
<label>CV Resume</label>
<input type="file" name="cv" class="form-control">
</div>

<div class="col-md-4">
<label>Passport Copy</label>
<input type="file" name="passport_file" class="form-control">
</div>

<div class="col-md-6">
<label>Driving License</label>
<input type="file" name="driving_license" class="form-control">
</div>

<div class="col-md-6">
<label>Other Document</label>
<input type="file" name="other_document" class="form-control">
</div>

</div>
</div>
</div>

{{-- SOCIAL --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Social Links</div>
<div class="card-body p-4">

<div class="row g-3">

<div class="col-md-6">
<label>Facebook</label>
<input type="url" name="facebook" class="form-control">
</div>

<div class="col-md-6">
<label>LinkedIn</label>
<input type="url" name="linkedin" class="form-control">
</div>

<div class="col-md-6">
<label>Instagram</label>
<input type="url" name="instagram" class="form-control">
</div>

<div class="col-md-6">
<label>YouTube</label>
<input type="url" name="youtube" class="form-control">
</div>

</div>
</div>
</div>

{{-- BIO --}}
<div class="card shadow-sm border-0 rounded-4 mb-4">
<div class="card-header bg-white fw-bold fs-5">Profile Summary</div>
<div class="card-body p-4">

<textarea name="bio" rows="5" class="form-control"></textarea>

</div>
</div>

{{-- FOOTER --}}
<div class="text-end mb-5">
<button class="btn btn-success px-5 py-2 rounded-pill">
<i class="fas fa-save me-2"></i> Save Candidate
</button>
</div>

</form>

</div>

<script>
flatpickr("input[name='birth_date']", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    defaultDate: "1995-01-01",
    monthSelectorType: "dropdown",
    disableMobile: true
});
flatpickr("input[name='passport_issue_date']", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    defaultDate: "1995-01-01",
    monthSelectorType: "dropdown",
    disableMobile: true
});
flatpickr("input[name='passport_expiry_date']", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    defaultDate: "1995-01-01",
    monthSelectorType: "dropdown",
    disableMobile: true
});
flatpickr("input[name='available_in']", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    defaultDate: "1995-01-01",
    monthSelectorType: "dropdown",
    disableMobile: true
});


</script>
<script>
document.addEventListener("DOMContentLoaded", function(){

let map = L.map('map').setView([33.6844,73.0479], 11);
let marker = L.marker([33.6844,73.0479], {draggable:true}).addTo(map);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap'
}).addTo(map);

const input   = document.getElementById('search_address');
const results = document.getElementById('address_results');

let timer = null;


/* ==========================
LIVE SEARCH WHILE TYPING
========================== */
input.addEventListener('keyup', function(){

    clearTimeout(timer);

    let query = this.value.trim();

    if(query.length < 3){
        results.style.display = 'none';
        return;
    }

    timer = setTimeout(function(){

        fetch('https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=8&q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {

            results.innerHTML = '';

            if(data.length === 0){
                results.style.display = 'none';
                return;
            }

            data.forEach(function(place){

                let item = document.createElement('div');
                item.classList.add('result-item');
                item.innerHTML = place.display_name;

                item.onclick = function(){
                    selectPlace(place);
                };

                results.appendChild(item);

            });

            results.style.display = 'block';

        });

    }, 400);

});


/* ==========================
SELECT RESULT
========================== */
function selectPlace(place){

    input.value = place.display_name;
    results.style.display = 'none';

    let lat = place.lat;
    let lon = place.lon;

    map.setView([lat,lon], 15);
    marker.setLatLng([lat,lon]);

    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lon;

    fillAddress(place);
}


/* ==========================
FILL FIELDS
========================== */
function fillAddress(place){

    let a = place.address || {};

    document.getElementById('full_address').value =
        place.display_name || '';

    document.getElementById('country').value =
        a.country || '';

    document.getElementById('state').value =
        a.state ||
        a.province ||
        a.region ||
        a.state_district ||
        a.county ||
        '';

    document.getElementById('city').value =
        a.city ||
        a.town ||
        a.village ||
        a.suburb ||
        a.municipality ||
        a.county ||
        '';
}


/* ==========================
DRAG MARKER
========================== */
marker.on('dragend', function(){

    let pos = marker.getLatLng();

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${pos.lat}&lon=${pos.lng}`)
    .then(res => res.json())
    .then(data => {

        document.getElementById('lat').value = pos.lat;
        document.getElementById('lng').value = pos.lng;

        fillAddress(data);

    });

});


/* Close dropdown */
document.addEventListener('click', function(e){
    if(!results.contains(e.target) && e.target !== input){
        results.style.display = 'none';
    }
});

});
</script>
@endsection