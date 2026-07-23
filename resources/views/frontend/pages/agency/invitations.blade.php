@extends('components.website.agency.layout.app')

@section('title', 'Invitations')

@section('main')

<style>

/* ===================================================== */
/* PAGE */
/* ===================================================== */

.invitation-page{
    padding:40px 0;
    background:#f4f7fb;
    min-height:100vh;
}

/* ===================================================== */
/* HEADER */
/* ===================================================== */

.invitation-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
    margin-bottom:30px;
}

.invitation-title{
    font-size:32px;
    font-weight:800;
    color:#111827;
    margin-bottom:5px;
}

.invitation-subtitle{
    color:#667085;
    font-size:15px;
}

/* ===================================================== */
/* BUTTON */
/* ===================================================== */

.invite-btn{
    border:none;
    background:linear-gradient(135deg,#0d6efd,#4f8cff);
    color:#fff;
    padding:14px 24px;
    border-radius:16px;
    font-weight:700;
    font-size:15px;
    box-shadow:0 10px 30px rgba(13,110,253,.18);
    transition:.3s;
}

.invite-btn:hover{
    transform:translateY(-2px);
}

/* ===================================================== */
/* STATS */
/* ===================================================== */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    background:#fff;
    border-radius:24px;
    padding:24px;
    box-shadow:0 10px 40px rgba(16,24,40,.04);
}

.stat-icon{
    width:58px;
    height:58px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
    margin-bottom:18px;
}

.stat-blue{
    background:#e7f1ff;
    color:#0d6efd;
}

.stat-green{
    background:#ecfdf3;
    color:#12b76a;
}

.stat-orange{
    background:#fff4e5;
    color:#f79009;
}

.stat-purple{
    background:#f4ebff;
    color:#7a5af8;
}

.stat-number{
    font-size:32px;
    font-weight:800;
    color:#111827;
}

.stat-label{
    color:#667085;
    margin-top:5px;
}

/* ===================================================== */
/* TABLE CARD */
/* ===================================================== */

.invitation-card{
    background:#fff;
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 10px 40px rgba(16,24,40,.04);
}

/* ===================================================== */
/* TABLE */
/* ===================================================== */

.invitation-table{
    margin-bottom:0;
}

.invitation-table thead th{
    background:#f8fafc;
    padding:20px;
    border:none;
    font-size:14px;
    font-weight:700;
    color:#475467;
}

.invitation-table tbody td{
    padding:22px 20px;
    vertical-align:middle;
    border-color:#eef2f7;
}

.invitation-table tbody tr{
    transition:.3s;
}

.invitation-table tbody tr:hover{
    background:#f8fbff;
}

/* ===================================================== */
/* BADGES */
/* ===================================================== */

.invitation-badge-company{
    background:#e7f1ff;
    color:#0d6efd;
    padding:10px 16px;
    border-radius:30px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
}

.invitation-badge-agency{
    background:#ecfdf3;
    color:#12b76a;
    padding:10px 16px;
    border-radius:30px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
}

/* ===================================================== */
/* STATUS */
/* ===================================================== */

.status-pending{
    background:#fff7e8;
    color:#f79009;
    border:1px solid #fedf89;
    padding:8px 14px;
    border-radius:30px;
    font-weight:700;
}

.status-accepted{
    background:#ecfdf3;
    color:#12b76a;
    border:1px solid #abefc6;
    padding:8px 14px;
    border-radius:30px;
    font-weight:700;
}

/* ===================================================== */
/* COMPANY */
/* ===================================================== */

.company-name{
    font-weight:700;
    color:#111827;
}

.company-email{
    color:#667085;
    font-size:14px;
}

/* ===================================================== */
/* EMPTY */
/* ===================================================== */

.empty-state{
    padding:70px 20px;
    text-align:center;
}

.empty-icon{
    width:90px;
    height:90px;
    margin:auto;
    border-radius:50%;
    background:#f4f7fb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:42px;
    color:#98a2b3;
    margin-bottom:20px;
}

/* ===================================================== */
/* RESPONSIVE */
/* ===================================================== */

@media(max-width:991px){

    .stats-grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:768px){

    .stats-grid{
        grid-template-columns:1fr;
    }

    .invitation-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .invitation-title{
        font-size:26px;
    }

}

</style>

<div class="invitation-page">

    <div class="container">

        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <div class="invitation-header">

            <div>

                <div class="invitation-title">

                    Invitations Management

                </div>

                <div class="invitation-subtitle">

                    Invite companies and agencies to collaborate with your recruitment network

                </div>

            </div>

            <button class="invite-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#inviteCompanyModal">

                <i class="ph ph-paper-plane-tilt me-2"></i>

                Send Invitation

            </button>

        </div>

        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}

        <div class="stats-grid">

            {{-- TOTAL --}}
            <div class="stat-card">

                <div class="stat-icon stat-blue">

                    <i class="ph ph-envelope-simple"></i>

                </div>

                <div class="stat-number">

                    {{ $invitations->count() }}

                </div>

                <div class="stat-label">

                    Total Invitations

                </div>

            </div>

            {{-- COMPANIES --}}
            <div class="stat-card">

                <div class="stat-icon stat-green">

                    <i class="ph ph-buildings"></i>

                </div>

                <div class="stat-number">

                    {{ $invitations->where('invitation_type','company')->count() }}

                </div>

                <div class="stat-label">

                    Companies

                </div>

            </div>

            {{-- AGENCIES --}}
            <div class="stat-card">

                <div class="stat-icon stat-purple">

                    <i class="ph ph-handshake"></i>

                </div>

                <div class="stat-number">

                    {{ $invitations->where('invitation_type','agency')->count() }}

                </div>

                <div class="stat-label">

                    Agencies

                </div>

            </div>

            {{-- PENDING --}}
            <div class="stat-card">

                <div class="stat-icon stat-orange">

                    <i class="ph ph-clock"></i>

                </div>

                <div class="stat-number">

                    {{ $invitations->count() }}

                </div>

                <div class="stat-label">

                    Pending

                </div>

            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}

        <div class="invitation-card">

            <div class="table-responsive">

                <table class="table invitation-table">

                    <thead>

                        <tr>

                            <th>
                                Type
                            </th>

                            <th>
                                Company / Agency
                            </th>

                            <th>
                                WhatsApp
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($invitations as $invitation)

                            <tr>

                                {{-- TYPE --}}
                                <td>

                                    @if($invitation->invitation_type == 'company')

                                        <span class="invitation-badge-company">

                                            <i class="ph ph-buildings me-2"></i>

                                            Company

                                        </span>

                                    @else

                                        <span class="invitation-badge-agency">

                                            <i class="ph ph-handshake me-2"></i>

                                            Agency

                                        </span>

                                    @endif

                                </td>

                                {{-- COMPANY --}}
                                <td>

                                    <div class="company-name">

                                        {{ $invitation->company_name }}

                                    </div>

                                    <div class="company-email">

                                        {{ $invitation->company_email }}

                                    </div>

                                </td>

                                {{-- WHATSAPP --}}
                                <td>

                                    @if($invitation->whatsapp)

                                        <span class="fw-semibold text-success">

                                            {{ $invitation->whatsapp }}

                                        </span>

                                    @else

                                        <span class="text-muted">

                                            N/A

                                        </span>

                                    @endif

                                </td>

                                {{-- STATUS --}}
                                <td>

                                    <span class="status-pending">

                                        Pending

                                    </span>

                                </td>

                                {{-- DATE --}}
                                <td>

                                    <span class="text-muted">

                                        {{ \Carbon\Carbon::parse($invitation->created_at)->diffForHumans() }}

                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5">

                                    <div class="empty-state">

                                        <div class="empty-icon">

                                            <i class="ph ph-envelope-simple-open"></i>

                                        </div>

                                        <h4 class="fw-bold mb-2">

                                            No Invitations Yet

                                        </h4>

                                        <p class="text-muted">

                                            Start inviting companies and agencies to grow your recruitment network.

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        {{-- PAGINATION --}}
        <div class="mt-4">

            {{ $invitations->links() }}

        </div>

    </div>

</div>

@include('frontend.pages.agency.partials.invite-company-modal')

@endsection