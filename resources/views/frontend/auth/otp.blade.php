@extends('frontend.layouts.app')

@section('title')
Verify OTP
@endsection

@section('css')

<style>

:root{
    --primary:#6366f1;
    --secondary:#8b5cf6;
    --success:#10b981;
    --danger:#ef4444;
    --dark:#0f172a;
    --gray:#64748b;
    --light:#f8fafc;
}

body{
    background:#f1f5f9;
    font-family:'Inter',sans-serif;
}

/* ===================================
    WRAPPER
=================================== */

.otp-wrapper{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:30px;
    position:relative;
    overflow:hidden;
}

.otp-wrapper::before{
    content:'';
    position:absolute;
    width:450px;
    height:450px;
    border-radius:50%;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    top:-180px;
    right:-180px;
    opacity:0.15;
}

.otp-wrapper::after{
    content:'';
    position:absolute;
    width:350px;
    height:350px;
    border-radius:50%;
    background:linear-gradient(135deg,#8b5cf6,#6366f1);
    bottom:-150px;
    left:-150px;
    opacity:0.12;
}

/* ===================================
    CARD
=================================== */

.otp-card{
    width:100%;
    max-width:520px;
    background:rgba(255,255,255,0.92);
    backdrop-filter:blur(20px);
    border-radius:36px;
    padding:45px;
    box-shadow:0 30px 80px rgba(15,23,42,0.12);
    position:relative;
    z-index:2;
    overflow:hidden;
}

.otp-card::before{
    content:'';
    position:absolute;
    width:220px;
    height:220px;
    border-radius:50%;
    background:rgba(99,102,241,0.06);
    top:-100px;
    right:-100px;
}

.otp-logo{
    width:90px;
    height:90px;
    border-radius:28px;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 28px;
    box-shadow:0 20px 40px rgba(99,102,241,0.3);
}

.otp-logo i{
    color:#fff;
    font-size:38px;
}

.otp-title{
    font-size:38px;
    font-weight:900;
    text-align:center;
    color:#0f172a;
    margin-bottom:12px;
}

.otp-subtitle{
    text-align:center;
    color:#64748b;
    line-height:1.8;
    margin-bottom:35px;
}

/* ===================================
    OPTIONS
=================================== */

.otp-options{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
    gap:16px;
    margin-bottom:30px;
}

.otp-option-btn{
    border:none;
    border-radius:22px;
    padding:22px 20px;
    background:#fff;
    border:1px solid #e2e8f0;
    transition:0.3s;
    cursor:pointer;
    text-align:center;
}

.otp-option-btn:hover{
    transform:translateY(-5px);
    border-color:#6366f1;
    box-shadow:0 15px 35px rgba(99,102,241,0.12);
}

.otp-option-btn.active{
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff;
    border-color:transparent;
}

.otp-option-btn i{
    font-size:28px;
    margin-bottom:12px;
    display:block;
}

.otp-option-btn span{
    font-weight:800;
    font-size:15px;
}

/* ===================================
    OTP SECTION
=================================== */

#otpSection{
    display:none;
    margin-top:15px;
}

.otp-label{
    text-align:center;
    font-weight:700;
    color:#0f172a;
    margin-bottom:20px;
}

.otp-inputs{
    display:flex;
    justify-content:center;
    gap:12px;
    margin-bottom:25px;
}

.otp-input{
    width:58px;
    height:65px;
    border-radius:18px;
    border:1px solid #dbe2ea;
    background:#fff;
    text-align:center;
    font-size:26px;
    font-weight:800;
    transition:0.3s;
}

.otp-input:focus{
    outline:none;
    border-color:#6366f1;
    box-shadow:0 0 0 5px rgba(99,102,241,0.12);
}

#countdownTimer{
    text-align:center;
    font-weight:700;
    margin-bottom:20px;
    color:#ef4444;
}

/* ===================================
    BUTTON
=================================== */

.submit-btn{
    width:100%;
    border:none;
    border-radius:20px;
    padding:17px;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff;
    font-size:16px;
    font-weight:800;
    transition:0.3s;
}

.submit-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 15px 35px rgba(99,102,241,0.2);
}

/* ===================================
    MOBILE
=================================== */

@media(max-width:576px){

    .otp-card{
        padding:30px 24px;
        border-radius:28px;
    }

    .otp-title{
        font-size:30px;
    }

    .otp-options{
        grid-template-columns:repeat(auto-fit,minmax(130px,1fr));
    }

    .otp-input{
        width:46px;
        height:56px;
        font-size:22px;
    }

    .otp-inputs{
        gap:8px;
    }

}

</style>

@endsection

@section('main')

@php
    $authUser = auth()->user() ?: auth('admin')->user();
    $userRole = $authUser?->role ?? null;

    // Map user role → Spatie role name
    $spatieName = match(true) {
        $userRole === 'candidate'                            => 'Seeker',
        in_array($userRole, ['company', 'agency', 'agent'])  => 'Employer',
        default                                              => null,
    };

    $spatieRole = $spatieName
        ? \App\Models\Role::where('name', $spatieName)->first()
        : null;

    // Load active methods from the role, or fall back to settings toggles
    if ($spatieRole) {
        $activeMethods = $spatieRole->activeOtpMethods()->get();
    } else {
        $activeMethods = collect();
        $emailOtpOn = \Illuminate\Support\Facades\Schema::hasColumn('settings', 'email_otp_verification')
            ? (bool) setting('email_otp_verification')
            : (bool) (setting('candidate_email_otp') || setting('employer_email_otp') || setting('email_verification'));
        $whatsappOtpOn = \Illuminate\Support\Facades\Schema::hasColumn('settings', 'whatsapp_otp_verification')
            ? (bool) setting('whatsapp_otp_verification')
            : (bool) (setting('candidate_whatsapp_otp') || setting('employer_whatsapp_otp'));
        if ($emailOtpOn) {
            $activeMethods->push((object)['name' => 'email']);
        }
        if ($whatsappOtpOn) {
            $activeMethods->push((object)['name' => 'whatsapp']);
        }
    }

    // Filter out methods the user has no contact info for
    $availableMethods = $activeMethods->filter(function($method) use ($authUser) {
        return match($method->name) {
            'email'    => !empty(trim($authUser?->email ?? '')),
            'whatsapp' => !empty(trim($authUser?->whatsapp ?? '')),
            'sms'      => !empty(trim($authUser?->phone ?? $authUser?->whatsapp ?? '')),
            default    => true,
        };
    });

    // Legacy vars kept for the auto-redirect check
    $emailOTP    = $availableMethods->contains('name', 'email');
    $whatsappOTP = $availableMethods->contains('name', 'whatsapp');
    $smsOTP      = $availableMethods->contains('name', 'sms');
@endphp

{{-- AUTO REDIRECT IF NO METHODS AVAILABLE --}}

@if($availableMethods->isEmpty())

<script>

window.location.href =
'{{ auth('admin')->check() ? route('admin.dashboard') : user_home_route() }}';

</script>

@endif

<div class="otp-wrapper">

    <div class="otp-card">

        {{-- ICON --}}

        <div class="otp-logo">

            <i class="fas fa-shield-alt"></i>

        </div>

        {{-- TITLE --}}

        <div class="otp-title">

            Verify Your Identity

        </div>

        <div class="otp-subtitle">

            Secure your account with OTP verification. Choose your preferred method below.

        </div>

        {{-- OPTIONS (dynamic — driven by role OTP methods in admin) --}}

        <div class="otp-options">

            @foreach($availableMethods as $method)
            @php
                $btnId = $method->name . 'Btn';
                $icon  = match($method->name) {
                    'email'    => 'fas fa-envelope',
                    'whatsapp' => 'fab fa-whatsapp',
                    'sms'      => 'fas fa-sms',
                    default    => 'fas fa-key',
                };
                $label = match($method->name) {
                    'email'    => 'Email OTP',
                    'whatsapp' => 'WhatsApp OTP',
                    'sms'      => 'SMS OTP',
                    default    => ucfirst($method->name) . ' OTP',
                };
            @endphp

            <button id="{{ $btnId }}"
                    class="otp-option-btn">

                <i class="{{ $icon }}"></i>

                <span>{{ $label }}</span>

            </button>

            @endforeach

        </div>

        {{-- OTP SECTION --}}

        <div id="otpSection">

            <div class="otp-label">

                Enter 6 Digit OTP

            </div>

            <div class="otp-inputs">

                <input type="text" maxlength="1" class="otp-input">

                <input type="text" maxlength="1" class="otp-input">

                <input type="text" maxlength="1" class="otp-input">

                <input type="text" maxlength="1" class="otp-input">

                <input type="text" maxlength="1" class="otp-input">

                <input type="text" maxlength="1" class="otp-input">

            </div>

            <div id="countdownTimer"></div>

            <button id="submitBtn"
                    class="submit-btn">

                Verify OTP

            </button>

        </div>

    </div>

</div>

@endsection


@section('script')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const otpSection = document.getElementById('otpSection');

const userId = @json(
    auth('admin')->check()
        ? auth('admin')->user()->id
        : (auth()->check() ? auth()->user()->id : null)
);

const redirectUrl = @json(
    auth('admin')->check()
        ? route('admin.dashboard')
        : user_home_route()
);

let selectedVia = null;

let timerInterval = null;

/*
|--------------------------------------------------------------------------
| BUTTON ACTIVE
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.otp-option-btn')
.forEach(btn => {

    btn.addEventListener('click', function(){

        document.querySelectorAll('.otp-option-btn')
        .forEach(b => b.classList.remove('active'));

        this.classList.add('active');

    });

});


/*
|--------------------------------------------------------------------------
| SEND OTP (dynamically wired from role methods)
|--------------------------------------------------------------------------
*/

@foreach($availableMethods as $method)
document.getElementById('{{ $method->name }}Btn')
?.addEventListener('click', () => sendOTP('{{ $method->name }}'));
@endforeach


function sendOTP(via){

    selectedVia = via;

    otpSection.style.display = 'block';

    startTimer();

    fetch('{{ route("send.otp") }}', {

        method:'POST',

        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },

        body:JSON.stringify({

            via:via,

            user_id:userId

        })

    })

    .then(res => res.json())

    .then(data => {

        if(data.success){

            Swal.fire({

                icon:'success',

                title:'OTP Sent',

                text:'OTP sent successfully via ' + via,

                timer:2000,

                showConfirmButton:false

            });

        }else{

            Swal.fire({

                icon:'error',

                title:'Failed',

                text:data.message || 'Failed to send OTP'

            });

        }

    })

    .catch(error => {

        console.error(error);

        Swal.fire({

            icon:'error',

            title:'Error',

            text:'Something went wrong'

        });

    });

}


/*
|--------------------------------------------------------------------------
| TIMER
|--------------------------------------------------------------------------
*/

function startTimer(){

    clearInterval(timerInterval);

    let time = 60;

    const timer = document.getElementById('countdownTimer');

    timer.style.display = 'block';

    timer.innerHTML = 'Resend OTP in 60 sec';

    timerInterval = setInterval(() => {

        time--;

        timer.innerHTML = 'Resend OTP in ' + time + ' sec';

        if(time <= 0){

            clearInterval(timerInterval);

            timer.innerHTML = `
                <button onclick="sendOTP(selectedVia)"
                        class="btn btn-primary btn-sm">
                    Resend OTP
                </button>
            `;

        }

    },1000);

}


/*
|--------------------------------------------------------------------------
| OTP INPUTS
|--------------------------------------------------------------------------
*/

const otpBoxes = document.querySelectorAll('.otp-input');

otpBoxes.forEach((input,index)=>{

    input.addEventListener('input',function(){

        this.value = this.value.replace(/[^0-9]/g,'');

        if(this.value.length === 1 && index < otpBoxes.length - 1){

            otpBoxes[index+1].focus();

        }

    });

    input.addEventListener('keydown',function(e){

        if(e.key === 'Backspace'
            && this.value === ''
            && index > 0){

            otpBoxes[index-1].focus();

        }

    });

});


/*
|--------------------------------------------------------------------------
| PASTE OTP
|--------------------------------------------------------------------------
*/

document.addEventListener('paste',function(e){

    let paste = e.clipboardData
        .getData('text')
        .replace(/\D/g,'');

    if(paste.length === 6){

        otpBoxes.forEach((input,i)=>{

            input.value = paste[i];

        });

    }

});


/*
|--------------------------------------------------------------------------
| VERIFY BUTTON
|--------------------------------------------------------------------------
*/

document.getElementById('submitBtn')
.addEventListener('click',()=>{

    let otp = '';

    otpBoxes.forEach(input => {

        otp += input.value;

    });

    if(otp.length !== 6){

        Swal.fire({

            icon:'warning',

            title:'Incomplete OTP',

            text:'Please enter complete OTP'

        });

        return;
    }

    submitOTP(otp);

});


/*
|--------------------------------------------------------------------------
| SUBMIT OTP
|--------------------------------------------------------------------------
*/

function submitOTP(otp){

    fetch('{{ url("/verify-otp") }}',{

        method:'POST',

        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':'{{ csrf_token() }}'
        },

        body:JSON.stringify({

            otp:otp,

            via:selectedVia,

            user_id:userId

        })

    })

    .then(res => res.json())

    .then(data => {

        if(data.success){

            Swal.fire({

                icon:'success',

                title:'Verified',

                text:'OTP verified successfully',

                timer:1500,

                showConfirmButton:false

            });

            setTimeout(()=>{

                window.location.href = redirectUrl;

            },1500);

        }else{

            Swal.fire({

                icon:'error',

                title:'Invalid OTP',

                text:data.message || 'OTP verification failed'

            });

        }

    })

    .catch(error=>{

        console.error(error);

        Swal.fire({

            icon:'error',

            title:'Error',

            text:'Verification failed'

        });

    });

}


/*
|--------------------------------------------------------------------------
| AUTO SMS OTP
|--------------------------------------------------------------------------
*/

if ('OTPCredential' in window) {

    navigator.credentials.get({

        otp:{

            transport:['sms']

        }

    })

    .then(otp => {

        if(otp && otp.code){

            otpBoxes.forEach((input,i)=>{

                input.value = otp.code[i];

            });

            submitOTP(otp.code);

        }

    })

    .catch(err => {

        console.log(err);

    });

}

</script>

@endsection