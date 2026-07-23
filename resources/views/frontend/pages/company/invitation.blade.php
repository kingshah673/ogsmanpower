@extends('layouts.app')

@section('content')

<form method="POST"
      action="{{ route('agency.send.company.invitation') }}">

    @csrf

    {{-- INVITATION TYPE --}}
    <div class="mb-4">

        <label class="fw-bold mb-2">

            Invitation Type

        </label>

        <select name="invitation_type"
                id="invitationType"
                class="form-control custom-input"
                required>

            <option value="company">

                Company

            </option>

            <option value="agency">

                Agency

            </option>

        </select>

    </div>

    {{-- NAME --}}
    <div class="mb-4">

        <label class="fw-bold mb-2">

            Company / Agency Name

        </label>

        <input type="text"
               name="company_name"
               class="form-control custom-input"
               required>

    </div>

    {{-- EMAIL --}}
    <div class="mb-4">

        <label class="fw-bold mb-2">

            Email Address

        </label>

        <input type="email"
               name="company_email"
               class="form-control custom-input"
               required>

    </div>

    {{-- WHATSAPP --}}
    <div class="mb-4">

        <label class="fw-bold mb-2">

            WhatsApp Number

        </label>

        <input type="tel"
               name="whatsapp"
               class="form-control custom-input intl-phone-input"
               placeholder="WhatsApp Number">

    </div>

    {{-- MESSAGE --}}
    <div class="mb-4">

        <label class="fw-bold mb-2">

            Invitation Message

        </label>

        <textarea name="message"
                  rows="6"
                  class="form-control custom-input">We would like to invite you to join Career Workforce.</textarea>

    </div>

    {{-- BUTTON --}}
    <button class="btn btn-primary w-100 custom-btn">

        Send Invitation

    </button>

</form>
@endsection