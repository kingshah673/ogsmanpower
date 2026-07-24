@extends('frontend.layouts.app')

@section('main')

{{-- HERO --}}
<section class="hero-premium">
    <div class="container text-center">
        <h1>Global Recruitment & Visa Experts</h1>
        <p>Delivering Workforce Solutions Across Middle East, Europe & Asia</p>

        <div class="hero-btns">
            <a href="https://wa.me/923005352636" class="btn gold-btn">WhatsApp Now</a>
            <a href="#contactForm" class="btn gold-btn">Send Inquiry</a>
        </div>
    </div>
</section>

{{-- CONTACT --}}
<section class="contact-premium">
    <div class="container">
        <div class="row">

            {{-- LEFT --}}
            <div class="col-lg-5">
                <div class="glass-box">

                    <h3 style="color:white;">Contact Information</h3>
                    <p>We specialize in overseas manpower supply, visa processing, and global recruitment.</p>

                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <p><a href="mailto:{{ $setting->email }}" style="color:inherit;">{{ $setting->email }}</a></p>
                        </div>
                    </div>

                    @if (!empty($cms_setting?->footer_phone_no))
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone</strong>
                            <p><a href="tel:{{ $cms_setting->footer_phone_no }}" style="color:inherit;">{{ $cms_setting->footer_phone_no }}</a></p>
                        </div>
                    </div>
                    @endif

                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Locations</strong>
                            <p>Pakistan • UAE • Saudi Arabia</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- FORM --}}
            <div class="col-lg-7">
                <div class="form-premium" id="contactForm">

                    <h4>Send Message</h4>

                    <form action="{{ route('module.contact.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="name" placeholder="Full Name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" placeholder="Email Address" class="form-control" required>
                            </div>
                        </div>

                        <input type="text" name="subject" placeholder="Subject" class="form-control mt-3" required>

                        <textarea name="message" rows="5" placeholder="Your Message..." class="form-control mt-3" required></textarea>

                        @if (config('captcha.active'))
                            <div class="mt-3">
                                {!! NoCaptcha::display() !!}
                            </div>
                        @endif

                        <button type="submit" class="btn gold-btn w-100 mt-4">
                            Send Message
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</section>

{{-- MAP --}}
<section>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3321.998148535668!2d73.06661688230514!3d33.63129000452174!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38df94db951a9af1%3A0x23ca33751eb280e!2sOGS%20MANPOWER%20(%20Licence%20No%202978Recruitment%20Agency%20)!5e0!3m2!1sen!2s!4v1773968044542!5m2!1sen!2s" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</section>

@endsection


@section('css')
<style>

/* HERO */
.hero-premium {
    background: radial-gradient(circle at top, rgba(201,169,110,0.2), transparent),
                url('/images/world-map-dark.png');
    background-size: cover;
    padding: 300px 0;
    color: #fff;
}

.hero-premium h1 {
    font-size: 52px;
    font-weight: 700;
    color: white;
}

.hero-premium p {
    opacity: 0.85;
    margin-top: 10px;
}

/* BUTTONS */
.gold-btn {
    background: linear-gradient(45deg,#c9a96e,#f5d487);
    color: #000;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
}

.outline-btn {
    border: 1px solid #fff;
    color: #fff;
    padding: 12px 30px;
    border-radius: 8px;
}

/* CONTACT */
.contact-premium {
    padding: 80px 0;
    background: #0b1220;
    color: #fff;
}

/* GLASS EFFECT */
.glass-box {
    backdrop-filter: blur(15px);
    background: rgba(255,255,255,0.05);
    padding: 40px;
    border-radius: 12px;
}

/* INFO */
.info-item {
    display: flex;
    margin-top: 25px;
}

.info-item i {
    background: #c9a96e;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-right: 15px;
}

/* FORM */
.form-premium {
    background: #fff;
    color: #000;
    padding: 45px;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.form-control {
    height: 50px;
    border-radius: 6px;
}

textarea.form-control {
    height: auto;
}

</style>
@endsection


@section('script')
<script src='https://www.google.com/recaptcha/api.js'></script>
@endsection