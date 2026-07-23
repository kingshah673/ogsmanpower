@extends('components.website.agent.new-sidebar')

@section('main')

<style>
    body {
        background: #f4f7fc;
    }

    .settings-wrapper {
        padding: 20px;
    }

    .card-premium {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: none;
    }

    .profile-card {
        text-align: center;
        padding: 30px 20px;
    }

    .profile-card img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f1f3f9;
    }

    .form-control {
        border-radius: 10px;
        height: 48px;
        border: 1px solid #e6eaf0;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: #4facfe;
    }

    .btn-premium {
        background: linear-gradient(135deg, #4facfe, #00f2fe);
        border: none;
        border-radius: 10px;
        padding: 12px 25px;
        color: white;
        font-weight: 600;
    }

    .section-title {
        font-weight: 600;
        margin-bottom: 20px;
    }

    .upload-box {
        border: 2px dashed #e0e6ed;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
    }

    .upload-box:hover {
        background: #f9fbff;
    }
</style>

<div class="container settings-wrapper">

    {{-- HEADER --}}
    <div class="mb-4">
        <h4 class="fw-bold">Account Settings ⚙️</h4>
        <p class="text-muted">Manage your profile and account details</p>
    </div>

    <div class="row">

        {{-- PROFILE CARD --}}
        <div class="col-md-4">
            <div class="card-premium profile-card">

                <img id="previewImage"
                     src="{{ isset($user->image) ? asset($user->image) : asset('backend/image/default.png') }}">

                <h5 class="mt-3">{{ $user->name }}</h5>
                <p class="text-muted">{{ $user->email }}</p>

                <label class="upload-box mt-3">
                    <input type="file" name="image" hidden id="imageInput">
                    Change Profile Image
                </label>

            </div>
        </div>

        {{-- FORM --}}
        <div class="col-md-8">
            <div class="card-premium">

                <h5 class="section-title">Basic Information</h5>

                <form action="{{ route('agent.setting.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="type" value="basic">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="name" value="{{ $user->name }}" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Username</label>
                            <input type="text" name="username" value="{{ $user->username }}" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ $user->email }}" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>WhatsApp</label>
                            <input type="tel" name="whatsapp" value="{{ $user->whatsapp }}" class="form-control intl-phone-input">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="mb-2">Profile Image</label>

                            <div class="upload-box">
                                <input type="file" name="image" id="fileUpload" hidden>
                                Click to upload image
                            </div>
                        </div>

                        <div class="col-md-12 text-end">
                            <button class="btn btn-premium">
                                Save Changes
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>

    </div>

</div>

<script>
    // Image preview
    document.getElementById('fileUpload').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    });

    document.querySelector('.upload-box').addEventListener('click', () => {
        document.getElementById('fileUpload').click();
    });
</script>

@endsection