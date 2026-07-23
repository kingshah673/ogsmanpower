{{-- ===================================================== --}}
{{-- PROFESSIONAL INVITATION MODAL --}}
{{-- ===================================================== --}}

<div class="modal fade"
     id="inviteCompanyModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content invitation-modal border-0">

            {{-- HEADER --}}
            <div class="modal-header invitation-header border-0">

                <div class="d-flex align-items-center">

                    <div class="invite-icon-wrap">

                        <i class="ph ph-paper-plane-tilt"></i>

                    </div>

                    <div class="ms-3">

                        <h4 class="fw-bold mb-1">

                            Send Invitation

                        </h4>

                        <p class="text-muted mb-0">

                            Invite companies or agencies to join your network

                        </p>

                    </div>

                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            {{-- FORM --}}
            <form method="POST"
                  action="{{ route('agency.send.company.invitation') }}">

                @csrf

                <div class="modal-body p-4">

                    {{-- INVITATION TYPE --}}
                    <div class="mb-4">

                        <label class="fw-semibold mb-2 d-block">

                            Invitation Type

                        </label>

                        <select name="invitation_type"
                                id="invitationType"
                                class="form-select custom-input"
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

                        <label class="fw-semibold mb-2 d-block">

                            Company / Agency Name

                        </label>

                        <input type="text"
                               name="company_name"
                               class="form-control custom-input"
                               placeholder="Enter company or agency name"
                               required>

                    </div>

                    {{-- EMAIL --}}
                    <div class="mb-4">

                        <label class="fw-semibold mb-2 d-block">

                            Email Address

                        </label>

                        <input type="email"
                               name="company_email"
                               class="form-control custom-input"
                               placeholder="company@example.com"
                               required>

                    </div>

                    {{-- WHATSAPP --}}
                    <div class="mb-4">

                        <label class="fw-semibold mb-2 d-block">

                            WhatsApp Number

                        </label>

                        <input type="tel"
                               name="whatsapp"
                               class="form-control custom-input intl-phone-input"
                               placeholder="WhatsApp Number">

                    </div>

                    {{-- MESSAGE --}}
                    <div class="mb-4">

                        <label class="fw-semibold mb-2 d-block">

                            Invitation Message

                        </label>

                        <textarea name="message"
                                  rows="6"
                                  class="form-control custom-textarea">We would like to invite you to join Career Workforce and collaborate with our recruitment network.</textarea>

                    </div>

                    {{-- INFO CARD --}}
                    <div class="invite-info-card">

                        <div class="d-flex align-items-start">

                            <div class="info-icon">

                                <i class="ph ph-info"></i>

                            </div>

                            <div class="ms-3">

                                <h6 class="fw-bold mb-1">

                                    Smart Invitation System

                                </h6>

                                <p class="mb-0 text-muted small">

                                    If the company or agency already exists,
                                    the system will notify you automatically.
                                    Otherwise an invitation email will be sent.

                                </p>

                            </div>

                        </div>

                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer border-0 px-4 pb-4">

                    <button type="button"
                            class="btn btn-light cancel-btn"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary submit-btn">

                        <i class="ph ph-paper-plane-tilt me-2"></i>

                        Send Invitation

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

{{-- ===================================================== --}}
{{-- STYLES --}}
{{-- ===================================================== --}}

<style>

/* MODAL */
.invitation-modal{
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,0.12);
}

/* HEADER */
.invitation-header{
    padding:28px 30px 10px;
}

/* ICON */
.invite-icon-wrap{
    width:60px;
    height:60px;
    border-radius:18px;
    background:linear-gradient(135deg,#0d6efd,#4f8cff);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:28px;
}

/* INPUT */
.custom-input{
    height:54px;
    border-radius:16px;
    border:1px solid #dbe2ea;
    padding:0 18px;
    font-size:15px;
    transition:.3s;
    box-shadow:none !important;
}

.custom-input:focus{
    border-color:#0d6efd;
    box-shadow:0 0 0 4px rgba(13,110,253,.1) !important;
}

/* TEXTAREA */
.custom-textarea{
    border-radius:16px;
    border:1px solid #dbe2ea;
    padding:18px;
    resize:none;
    box-shadow:none !important;
}

.custom-textarea:focus{
    border-color:#0d6efd;
    box-shadow:0 0 0 4px rgba(13,110,253,.1) !important;
}

/* INFO CARD */
.invite-info-card{
    background:#f8fafc;
    border:1px solid #e5edf5;
    border-radius:18px;
    padding:20px;
}

.info-icon{
    width:42px;
    height:42px;
    border-radius:12px;
    background:#e7f1ff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#0d6efd;
    font-size:20px;
}

/* BUTTONS */
.cancel-btn{
    border-radius:14px;
    padding:12px 24px;
    font-weight:600;
}

.submit-btn{
    border-radius:14px;
    padding:12px 28px;
    font-weight:600;
    background:linear-gradient(135deg,#0d6efd,#4f8cff);
    border:none;
}

/* MOBILE */
@media(max-width:768px){

    .invitation-header{
        padding:22px 20px 10px;
    }

    .modal-body{
        padding:20px !important;
    }

    .modal-footer{
        padding:20px !important;
    }

}

</style>