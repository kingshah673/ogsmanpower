@extends('components.website.agency.new-sidebar')

@section('main')

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h4>Edit Candidate</h4>
        <a href="{{ route('agency.candidates.index') }}" class="btn btn-light">← Back</a>
    </div>

    <form action="{{ route('agency.candidates.update',$candidate->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- PERSONAL INFO --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Personal Information</strong></div>
            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="{{ $candidate->first_name }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="{{ $candidate->last_name }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="male" {{ $candidate->gender=='male'?'selected':'' }}>Male</option>
                            <option value="female" {{ $candidate->gender=='female'?'selected':'' }}>Female</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Birth Date</label>
                        <input type="date" name="birth_date" value="{{ $candidate->birth_date }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Marital Status</label>
                        <input type="text" name="marital_status" value="{{ $candidate->marital_status }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Whatsapp Number</label>
                        <input type="tel" name="whatsapp_number" value="{{ $candidate->whatsapp_number }}" class="form-control intl-phone-input">
                    </div>

                </div>

            </div>
        </div>

        {{-- PASSPORT --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Passport Details</strong></div>
            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Passport Number</label>
                        <input type="text" name="passport_number" value="{{ $candidate->passport_number }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Issue Date</label>
                        <input type="date" name="passport_issue_date" value="{{ $candidate->passport_issue_date }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Expiry Date</label>
                        <input type="date" name="passport_expiry_date" value="{{ $candidate->passport_expiry_date }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Place of Issue</label>
                        <input type="text" name="place_of_issue" value="{{ $candidate->place_of_issue }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>CNIC</label>
                        <input type="text" name="cnic_number" value="{{ $candidate->cnic_number }}" class="form-control">
                    </div>

                </div>

            </div>
        </div>

        {{-- JOB INFO --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Job Information</strong></div>
            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Expected Salary</label>
                        <input type="text" name="expected_salary" value="{{ $candidate->expected_salary }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Expected Location</label>
                        <input type="text" name="expected_location" value="{{ $candidate->expected_location }}" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="available" {{ $candidate->status=='available'?'selected':'' }}>Available</option>
                            <option value="not_available" {{ $candidate->status=='not_available'?'selected':'' }}>Not Available</option>
                        </select>
                    </div>

                </div>

            </div>
        </div>

        {{-- ADDRESS --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Address</strong></div>
            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Address</label>
                        <input type="text" name="address" value="{{ $candidate->address }}" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>District</label>
                        <input type="text" name="district" value="{{ $candidate->district }}" class="form-control">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Country</label>
                        <input type="text" name="country" value="{{ $candidate->country }}" class="form-control">
                    </div>

                </div>

            </div>
        </div>

        {{-- DOCUMENTS --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Documents</strong></div>
            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control">
                        @if($candidate->photo)
                            <img src="{{ asset('storage/'.$candidate->photo) }}" width="60" class="mt-2">
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>CV</label>
                        <input type="file" name="cv" class="form-control">
                        @if($candidate->cv)
                            <a href="{{ asset('storage/'.$candidate->cv) }}" target="_blank">View CV</a>
                        @endif
                    </div>

                </div>

            </div>
        </div>

        {{-- BIO --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header"><strong>Profile</strong></div>
            <div class="card-body">
                <textarea name="bio" class="form-control" rows="4">{{ $candidate->bio }}</textarea>
            </div>
        </div>

        <button class="btn btn-primary">Update Candidate</button>

    </form>

</div>

@endsection