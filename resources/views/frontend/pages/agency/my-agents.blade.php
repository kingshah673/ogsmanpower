@extends('components.website.agency.layout.app')

@section('title', 'My Agents')

@section('main')

<style>
body { background: #f3f4f6; }
.page-wrapper { max-width: 1000px; margin: auto; padding: 30px 15px; }
.card-ui { background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 20px; }
.title  { font-size: 17px; font-weight: 600; }
.sub    { color: #6b7280; font-size: 13px; }
.badge-pending  { background: #fef9c3; color: #b45309; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
.badge-accepted { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
.badge-expired  { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
.btn-primary-sm { background:#0a66c2; color:#fff; padding:6px 14px; border-radius:6px; font-size:13px; border:none; cursor:pointer; }
.btn-primary-sm:hover { background:#0958a8; color:#fff; }
</style>

<div class="page-wrapper">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="title">My Agents / Facilitators</div>
        <button class="btn-primary-sm" data-bs-toggle="modal" data-bs-target="#inviteAgentModal">
            + Invite Agent / Facilitator
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    {{-- ACTIVE AGENTS --}}
    <div class="card-ui">
        <div class="title mb-3">Active Agents ({{ $agents->count() }})</div>
        @forelse($agents as $agent)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <div class="fw-semibold">{{ $agent->name }}</div>
                <div class="sub">{{ $agent->email }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if((int) $agent->status === 1)
                    <span class="badge-accepted">Active</span>
                @else
                    <span class="badge-expired">Suspended</span>
                @endif
                <form method="POST" action="{{ route('agency.agent.toggle_status', $agent->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        {{ (int) $agent->status === 1 ? 'Suspend' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-3">
            No agents linked yet. Send an invitation to add your first agent.
        </div>
        @endforelse
    </div>

    {{-- PENDING INVITES --}}
    <div class="card-ui">
        <div class="title mb-3">Pending Invitations ({{ $invites->where('accepted_at', null)->count() }})</div>
        @forelse($invites as $invite)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <div class="fw-semibold">{{ $invite->agent_name }}</div>
                <div class="sub">{{ $invite->agent_email }}</div>
                <div class="sub">Expires {{ $invite->expires_at->format('d M Y') }}</div>
            </div>
            <div>
                @if($invite->accepted_at)
                    <span class="badge-accepted">Accepted</span>
                @elseif($invite->isExpired())
                    <span class="badge-expired">Expired</span>
                @else
                    <span class="badge-pending">Pending</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-3">No invitations sent yet.</div>
        @endforelse
    </div>

</div>

{{-- INVITE MODAL --}}
<div class="modal fade" id="inviteAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Invite an Agent / Facilitator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('agency.send.agent.invite') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Agent Name</label>
                        <input type="text" name="agent_name" class="form-control rounded-3"
                               placeholder="Full name of the agent" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Agent Email</label>
                        <input type="email" name="agent_email" class="form-control rounded-3"
                               placeholder="agent@example.com" required>
                    </div>
                    <div class="alert alert-info small rounded-3 mb-0">
                        An email invitation will be sent. The link is valid for 7 days.
                        Once the agent registers using the link, their account will be
                        automatically linked to your agency.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 fw-semibold">
                        Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
