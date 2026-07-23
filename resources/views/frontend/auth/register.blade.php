@extends('frontend.auth.layouts.auth')

@section('content')
<div class="login-page min-vh-100 d-flex align-items-center">

    <div class="container">
        <div class="row align-items-center">

            <!-- LEFT SIDE (PROMOTION) -->
            <div class="col-lg-6 d-none d-lg-flex">
                <div class="promo-box w-100">

                    @php
                        $registrationType = old('type', request()->query('type', ''));
                        $registrationMeta = registrationTypeMeta($registrationType);
                    @endphp

                    <h1>{{ $registrationMeta['headline'] ?: 'Build Your Global Career' }}</h1>

                    <p>{{ $registrationMeta['description'] ?: 'Join thousands of professionals connecting with top employers worldwide. Your journey starts here.' }}</p>

                    @if (in_array($registrationMeta['type'], ['seeker', 'domestic_worker', 'abroad_student', 'work_permit_seeker'], true) || $registrationMeta['type'] === '')
                    <ul class="promo-list">
                        <li>✔ Premium Job Opportunities</li>
                        <li>✔ International Hiring Network</li>
                        <li>✔ Trusted by Employers</li>
                    </ul>
                    @elseif (in_array($registrationMeta['type'], ['employer', 'labour_supply', 'university'], true))
                    <ul class="promo-list">
                        <li>✔ Post jobs nationally & internationally</li>
                        <li>✔ Access verified candidates</li>
                        <li>✔ Manage hiring in one dashboard</li>
                    </ul>
                    @elseif (in_array($registrationMeta['type'], ['agency', 'domestic_office', 'eu_permit_specialist'], true))
                    <ul class="promo-list">
                        <li>✔ Manage clients & placements</li>
                        <li>✔ Build your agent network</li>
                        <li>✔ Scale recruitment operations</li>
                    </ul>
                    @elseif (in_array($registrationMeta['type'], ['agent', 'hr_referral'], true))
                    <ul class="promo-list">
                        <li>✔ Manage candidate portfolios</li>
                        <li>✔ Apply to jobs on their behalf</li>
                        <li>✔ Track application status</li>
                    </ul>
                    @elseif ($registrationMeta['type'] === 'broker')
                    <ul class="promo-list">
                        <li>✔ Create demand / job orders</li>
                        <li>✔ Route demand to Recruitment Agencies</li>
                        <li>✔ Track open and routed requests</li>
                    </ul>
                    @endif

                </div>
            </div>

            <!-- RIGHT SIDE (FORM) -->
            <div class="col-lg-6 col-md-12">
                <div class="form-wrapper">

                    <div class="auth-box2">

                        <!-- ===== YOUR ORIGINAL FORM START (UNCHANGED) ===== -->
                        <form id="dynamicForm" action="{{ route('register') }}" method="POST">
                            @csrf

                            @php
                                $registrationType = old('type', request()->query('type', ''));
                                $registrationMeta = registrationTypeMeta($registrationType);
                                $registrationRole = old('role', $registrationMeta['role']);
                            @endphp

                            <input type="hidden" name="type" id="registrationType" value="{{ $registrationMeta['type'] }}">
                            <input type="hidden" name="role" id="registrationRole" value="{{ $registrationRole }}">

                            <h4 class="rt-mb-20">{{ __('create_account') }}</h4>

                            @if ($errors->any())
                                <div class="alert alert-danger rt-mb-15" style="border-radius:8px; font-size:14px;">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @error('type')
                                <div class="alert alert-warning rt-mb-15">{{ $message }}</div>
                            @enderror

                            <span class="d-block body-font-3 text-gray-600 rt-mb-32">
                                {{ __('already_have_account') }}
                                <a href="{{ route('login') }}">{{ __('log_in') }}</a>
                            </span>

                            <!-- Role banner -->
                            <div class="tw-bg-[#F1F2F4] tw-rounded-lg tw-mb-6 tw-p-3 text-center">
                                @if ($registrationMeta['label'])
                                    <p class="fw-bold mb-1">
                                        {{ __('you_are_registering_as') }}
                                        <span class="role-blink">{{ $registrationMeta['label'] }}</span>
                                    </p>
                                    <a href="javascript:void(0)"
                                       class="small text-muted"
                                       data-bs-toggle="modal"
                                       data-bs-target="#registerTypeModal">
                                        {{ __('change_role') }}
                                    </a>
                                @else
                                    <p class="fw-bold mb-2">{{ __('choose_how_to_register') }}</p>
                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn btn-sm btn-outline-primary">{{ __('seeker') }}</a>
                                        <a href="{{ route('register', ['type' => 'employer']) }}" class="btn btn-sm btn-outline-primary">{{ __('employer') }}</a>
                                        <a href="{{ route('register', ['type' => 'agency']) }}" class="btn btn-sm btn-outline-primary">{{ __('recruitment_agency') }}</a>
                                        <a href="{{ route('register', ['type' => 'agent']) }}" class="btn btn-sm btn-outline-primary">Agent / Facilitator</a>
                                        <a href="{{ route('register', ['type' => 'broker']) }}" class="btn btn-sm btn-outline-primary">Broker / Middleman</a>
                                        <a href="{{ route('register', ['type' => 'labour_supply']) }}" class="btn btn-sm btn-outline-primary">Labour Supply Office</a>
                                        <a href="{{ route('register', ['type' => 'hr_referral']) }}" class="btn btn-sm btn-outline-primary">HR Referral Partner</a>
                                        <a href="{{ route('register', ['type' => 'domestic_office']) }}" class="btn btn-sm btn-outline-primary">Domestic Worker Office</a>
                                        <a href="{{ route('register', ['type' => 'domestic_worker']) }}" class="btn btn-sm btn-outline-primary">Selected Domestic Worker</a>
                                        <a href="{{ route('register', ['type' => 'university']) }}" class="btn btn-sm btn-outline-primary">University / College / School</a>
                                        <a href="{{ route('register', ['type' => 'abroad_student']) }}" class="btn btn-sm btn-outline-primary">Abroad Edu Student</a>
                                        <a href="{{ route('register', ['type' => 'eu_permit_specialist']) }}" class="btn btn-sm btn-outline-primary">EU Work Permit Specialist</a>
                                        <a href="{{ route('register', ['type' => 'work_permit_seeker']) }}" class="btn btn-sm btn-outline-primary">Work Permit Seeker</a>
                                    </div>
                                @endif
                            </div>

                            <!-- Name -->
                            <div class="fromGroup rt-mb-15">
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="{{ __('full_name') }}">
                                @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <!-- Email -->
                            <div class="fromGroup rt-mb-15">
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="{{ __('email_address') }}">
                                @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>

                            <!-- WhatsApp -->
                            <x-forms.intl-phone-input
                                name="whatsapp"
                                id="whatsappNumber"
                                placeholder="WhatsApp Number"
                                :optional="true"
                                error-target="validationMessage"
                                invalid-message="Please enter a valid WhatsApp number or leave it empty."
                            />

                            <!-- HR Dropdown -->
                            <div id="hrResourceContainer" style="display:none;" class="fromGroup rt-mb-15">
                                <select name="hr_resource" id="hr_resource" class="form-control">
                                    <option selected>{{ __('select_one') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Company Registration Number (company only) -->
                            <div class="fromGroup rt-mb-15" id="regNumberGroup" style="display:none">
                                <input type="text"
                                       name="registration_number"
                                       id="registration_number"
                                       value="{{ old('registration_number') }}"
                                       class="form-control @error('registration_number') is-invalid @enderror"
                                       placeholder="Company Registration Number (e.g. CR-12345)">
                                <small class="text-muted">Required for company accounts. Use your official trade/CR number.</small>
                                @error('registration_number')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Agency MPD License Number (agency only) -->
                            <div class="fromGroup rt-mb-15" id="licenseNumberGroup" style="display:none">
                                <input type="text"
                                       name="license_number"
                                       id="license_number"
                                       value="{{ old('license_number') }}"
                                       class="form-control @error('license_number') is-invalid @enderror"
                                       placeholder="MPD / Agency License Number (e.g. 2978, MPD-2978)">
                                <small class="text-muted">Required for recruitment agencies. Use your official BEOE / MPD license number.</small>
                                @error('license_number')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Password -->
<div class="fromGroup rt-mb-15 position-relative">
    <input type="password"
           name="password"
           id="password"
           class="form-control @error('password') is-invalid @enderror"
           placeholder="{{ __('password') }}"
           minlength="8"
           maxlength="16"
           required>

    <div onclick="togglePassword('password','eyeIcon')"
         id="eyeIcon"
         style="position:absolute; top:50%; right:10px; cursor:pointer;">
        <i class="ph-eye"></i>
    </div>
    <small class="text-danger" id="passwordError"></small>
    @error('password')<small class="text-danger">{{ $message }}</small>@enderror
</div>

<!-- Confirm Password -->
<div class="fromGroup rt-mb-15 position-relative">
    <input type="password"
           name="password_confirmation"
           id="password_confirmation"
           class="form-control"
           placeholder="{{ __('confirm_password') }}"
           minlength="8"
           maxlength="16"
           required>
           
    <div onclick="togglePassword('password_confirmation','eyeIcon2')" 
         id="eyeIcon2"
         style="position:absolute; top:50%; right:10px; cursor:pointer;">
        <i class="ph-eye"></i>
    </div>
</div>


                            <!-- Terms -->
                            <div class="d-flex flex-wrap rt-mb-30">
                                <div class="flex-grow-1">
                                    <div class="form-check">
                                        <input type="checkbox" id="term" class="form-check-input">
                                        <label for="term">
                                            {{ __('i_have_read_and_agree_with') }}
                                            <a href="{{ url('terms-condition') }}" target="_blank">
                                                {{ __('terms_of_service') }}
                                            </a>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button id="submitButton" type="submit" class="btn btn-primary w-100" disabled>
                                {{ __('create_account') }}
                            </button>

                        </form>
                        <!-- ===== YOUR ORIGINAL FORM END ===== -->

                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection


@section('style')
<style>
.login-page {
    background: #f8fafc;
}

.role-blink {
    color: #25478F;
    font-weight: 700;
    animation: blinkSoft 1.2s infinite;
}

@keyframes blinkSoft {
    0%   { opacity: 1; }
    50%  { opacity: 0.4; }
    100% { opacity: 1; }
}
/* LEFT SIDE */
.promo-box {
    padding: 60px;
}

.promo-box h1 {
    font-size: 42px;
    font-weight: 700;
}

.promo-box p {
    margin: 20px 0;
    color: #64748b;
}

.promo-list {
    list-style: none;
    padding: 0;
}

.promo-list li {
    margin-bottom: 10px;
}

/* FORM */
.form-wrapper {
    display: flex;
    justify-content: center;
}

.auth-box2 {
    width: 100%;
    max-width: 420px;
    padding: 35px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.08);
}

/* INPUT */
.form-control {
    border-radius: 8px;
    height: 45px;
}

/* BUTTON */
.btn-primary {
    border-radius: 8px;
    height: 45px;
}

/* MOBILE */
@media(max-width: 992px){
    .promo-box {
        display: none;
    }
}
</style>
@endsection


@section('script')
<script>
// PASSWORD TOGGLE (UNCHANGED)
function togglePassword(fieldId, iconId){
    let field = document.getElementById(fieldId);
    let icon = document.getElementById(iconId).querySelector('i');

    if(field.type === 'password'){
        field.type='text';
        icon.classList.replace('ph-eye','ph-eye-slash');
    } else {
        field.type='password';
        icon.classList.replace('ph-eye-slash','ph-eye');
    }
}

// ENABLE BUTTON (UNCHANGED)
const formFields = ['name','email','password','password_confirmation'];
const submitButton = document.getElementById('submitButton');
const term = document.getElementById('term');

function checkEnable(){
    let enable = formFields.every(id => document.getElementById(id).value.length>0) && term.checked;
    submitButton.disabled = !enable;
}

formFields.forEach(id => document.getElementById(id).addEventListener('keyup',checkEnable));
term.addEventListener('change',checkEnable);

// HR DROPDOWN — only for HR registration type
const roleInput = document.getElementById('registrationRole');
const typeInput = document.getElementById('registrationType');

function syncHrResourceVisibility() {
    const role = roleInput ? roleInput.value : '';
    const hrContainer = document.getElementById('hrResourceContainer');
    if (hrContainer) {
        hrContainer.style.display = role === 'hr' ? 'block' : 'none';
    }
}

if (roleInput) {
    syncHrResourceVisibility();
}
</script>
<script>
document.getElementById('password').addEventListener('input', function () {
    let password = this.value;
    let error = document.getElementById('passwordError');

    if (password.length < 8) {
        error.innerText = "Password must be at least 8 characters.";
    } 
    else if (password.length > 16) {
        error.innerText = "Password must not exceed 16 characters.";
    } 
    else {
        error.innerText = "";
    }
});
</script>
<script>
// ── Registration / license number fields: show based on registration type ──
function syncRegNumberVisibility(type) {
    const companyDocTypes = ['company', 'employer'];
    const agencyLicenseTypes = ['agency'];
    const isCompany = companyDocTypes.includes(type);
    const isAgency  = agencyLicenseTypes.includes(type);

    const regGrp  = document.getElementById('regNumberGroup');
    const regInp  = document.getElementById('registration_number');
    const licGrp  = document.getElementById('licenseNumberGroup');
    const licInp  = document.getElementById('license_number');

    if (regGrp) regGrp.style.display = isCompany ? 'block' : 'none';
    if (regInp) {
        regInp.required = isCompany;
        if (!isCompany) regInp.value = '';
    }

    if (licGrp) licGrp.style.display = isAgency ? 'block' : 'none';
    if (licInp) {
        licInp.required = isAgency;
        if (!isAgency) licInp.value = '';
    }
}

// Re-evaluate the submit button when either number field changes
const regInput = document.getElementById('registration_number');
if (regInput) regInput.addEventListener('input', checkEnable);

const licInput = document.getElementById('license_number');
if (licInput) licInput.addEventListener('input', checkEnable);

// Patch checkEnable to also require the active number field when visible
const _origCheckEnable = checkEnable;
checkEnable = function () {
    const regGrp = document.getElementById('regNumberGroup');
    const regInp = document.getElementById('registration_number');
    const regOk  = !regGrp || regGrp.style.display === 'none' || (regInp && regInp.value.trim().length > 0);

    const licGrp = document.getElementById('licenseNumberGroup');
    const licInp = document.getElementById('license_number');
    const licOk  = !licGrp || licGrp.style.display === 'none' || (licInp && licInp.value.trim().length > 0);

    const enable = ['name','email','password','password_confirmation'].every(
        id => document.getElementById(id).value.length > 0
    ) && document.getElementById('term').checked && regOk && licOk;
    document.getElementById('submitButton').disabled = !enable;
};

document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    const urlType = params.get('type');
    const role = roleInput ? roleInput.value : '';
    const type = urlType || (typeInput ? typeInput.value : '') || role;

    syncRegNumberVisibility(type || role);
    syncHrResourceVisibility();
});
</script>
@endsection