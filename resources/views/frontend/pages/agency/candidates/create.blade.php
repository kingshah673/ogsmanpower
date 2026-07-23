@extends('components.website.agency.new-sidebar')


@section('main')

<div class="container mt-4">
    {{-- Alerts --}}
    @if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

<h3 class="mb-4">Add Candidate Profile</h3> 
<div class="upload-box position-relative text-center">

    <input type="file" id="cvUpload" name="cv" class="file-input" accept=".pdf,.doc,.docx">

    <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
    <p class="mb-1">Upload CV & Auto Fill</p>
    <small class="text-muted">AI will extract details</small>

    <div class="file-name mt-2 text-success"></div>

</div>

{{-- OCR BUTTON --}}
<button type="button" class="btn btn-dark mt-2" onclick="uploadCV()">
    <i class="fas fa-magic"></i> Auto Fill from CV
</button>

<form action="{{ route('agency.candidates.store') }}" method="POST" enctype="multipart/form-data">
@csrf

{{-- ================= PERSONAL INFO ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Personal Information</strong></div>
    <div class="card-body">

        <div class="row">

            <div class="col-md-4">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>

            <div class="col-md-4 mt-2">
                <label>Date of Birth</label>
                <input type="date" name="birth_date" class="form-control">
            </div>

            <div class="col-md-4 mt-2">
                <label>Marital Status</label>
                <select name="marital_status" class="form-control">
                    <option value="">Select</option>
                    <option value="single">Single</option>
                    <option value="married">Married</option>
                </select>
            </div>

            <div class="col-md-4 mt-2">
                <label>WhatsApp</label>
                <input type="tel" name="whatsapp_number" class="form-control intl-phone-input">
            </div>

        </div>

    </div>
</div>

{{-- ================= PASSPORT ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Passport Details</strong></div>
    <div class="card-body">

        <div class="row">

            <div class="col-md-3">
                <label>Passport Number</label>
                <input type="text" name="passport_number" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Issue Date</label>
                <input type="date" name="passport_issue_date" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Expiry Date</label>
                <input type="date" name="passport_expiry_date" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Place of Issue</label>
                <input type="text" name="place_of_issue" class="form-control">
            </div>

        </div>

    </div>
</div>

{{-- ================= CNIC ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>CNIC & Email</strong></div>
    <div class="card-body">
<div class="row">
    <div class="col-md-6">
        <label>CNIC Number</label>
        <input type="text" name="cnic_number" class="form-control">
</div>
<div class="col-md-6">

        <label>Email of Candidate</label>
        <input type="text" name="email" class="form-control">
        </div>
</div>
    </div>
</div>

{{-- ================= JOB INFO ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Job Preferences</strong></div>
    <div class="card-body">

        <div class="row">

            <div class="col-md-4">
                <label>Expected Salary</label>
                <input type="text" name="expected_salary" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Preferred Location</label>
                <input type="text" name="expected_location" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="available">Available</option>
                    <option value="not_available">Not Available</option>
                </select>
            </div>

        </div>

    </div>
</div>

{{-- ================= ADDRESS ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Address</strong></div>
    <div class="card-body">

        <div class="row">

            <div class="col-md-6">
                <label>Address</label>
                <input type="text" name="address" class="form-control">
            </div>

            <div class="col-md-3">
                <label>City</label>
                <input type="text" name="district" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Country</label>
                <input type="text" name="country" class="form-control">
            </div>

        </div>

    </div>
</div>

{{-- ================= DOCUMENTS ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Documents</strong></div>

    <div class="card-body">
        <div class="row">

            {{-- PHOTO --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">Profile Photo</label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="photo" class="file-input" accept="image/*">
                    <i class="fas fa-image fa-2x text-primary mb-2"></i>
                    <p class="mb-1">Upload Photo</p>
                    <small class="text-muted">JPG, PNG</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>

            {{-- CV --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">CV / Resume</label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="cv" class="file-input" accept=".pdf,.doc,.docx">
                    <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                    <p class="mb-1">Upload CV</p>
                    <small class="text-muted">PDF, DOC</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>

            {{-- PASSPORT --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">Passport Copy</label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="passport_file" class="file-input" accept="image/*,.pdf">
                    <i class="fas fa-passport fa-2x text-warning mb-2"></i>
                    <p class="mb-1">Upload Passport</p>
                    <small class="text-muted">Image or PDF</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>

            {{-- DRIVING LICENSE --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">Driving License</label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="driving_license" class="file-input" accept="image/*,.pdf">
                    <i class="fas fa-id-card fa-2x text-info mb-2"></i>
                    <p class="mb-1">Upload License</p>
                    <small class="text-muted">Image or PDF</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>
{{-- CNIC --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">CNIC </label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="cnic_document" class="file-input" accept="image/*,.pdf">
                    <i class="fas fa-folder-open fa-2x text-secondary mb-2"></i>
                    <p class="mb-1">Upload File</p>
                    <small class="text-muted">Image or PDF</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>
            {{-- OTHER DOCUMENT --}}
            <div class="col-md-4 mb-3">
                <label class="form-label">Other Document</label>
                <div class="upload-box position-relative text-center">
                    <input type="file" name="other_document" class="file-input" accept="image/*,.pdf">
                    <i class="fas fa-folder-open fa-2x text-secondary mb-2"></i>
                    <p class="mb-1">Upload File</p>
                    <small class="text-muted">Any format</small>
                    <div class="file-name mt-2 text-success"></div>
                </div>
            </div>

        </div>
    </div>
</div>
{{-- ================= BIO ================= --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header"><strong>Profile Summary</strong></div>
    <div class="card-body">

        <textarea name="bio" class="form-control" rows="4"></textarea>

    </div>
</div>

{{-- ================= SUBMIT ================= --}}
<div class="text-end">
    <button class="btn btn-success px-4">Save Candidate</button>
</div>

</form>

</div>

@endsection
