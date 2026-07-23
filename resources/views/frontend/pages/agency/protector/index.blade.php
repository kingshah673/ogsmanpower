@extends('components.website.agency.new-sidebar')

@section('main')

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Protector Clearance</h4>
            <small class="text-muted">Track protectorate submission and clearance for candidates before deployment.</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProtectorModal">
            <i class="fas fa-plus"></i> New Submission
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Reference #</th>
                        <th>Submission</th>
                        <th>Clearance</th>
                        <th>Expiry</th>
                        <th style="width: 300px;">Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->candidate->first_name ?? '' }} {{ $record->candidate->last_name ?? '' }}</td>
                            <td>{{ $record->reference_number ?: '—' }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($record->submission_status)) }}</td>
                            <td><span class="{{ $record->clearanceBadgeClass() }}">{{ ucfirst($record->clearance_status) }}</span>
                                @if($record->clearance_status === 'rejected' && $record->rejection_reason)
                                    <div class="small text-danger">{{ $record->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>{{ $record->expiry_date ? $record->expiry_date->format('d M Y') : '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('agency.protector.update', $record->id) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-1 align-items-center">
                                    @csrf
                                    <select name="submission_status" class="form-select form-select-sm" style="width:auto;">
                                        <option value="not_submitted" @selected($record->submission_status === 'not_submitted')>Not submitted</option>
                                        <option value="submitted" @selected($record->submission_status === 'submitted')>Submitted</option>
                                        <option value="under_review" @selected($record->submission_status === 'under_review')>Under review</option>
                                    </select>
                                    <select name="clearance_status" class="form-select form-select-sm" style="width:auto;">
                                        <option value="pending" @selected($record->clearance_status === 'pending')>Pending</option>
                                        <option value="cleared" @selected($record->clearance_status === 'cleared')>Cleared</option>
                                        <option value="rejected" @selected($record->clearance_status === 'rejected')>Rejected</option>
                                    </select>
                                    <input type="date" name="expiry_date" class="form-control form-control-sm" style="width:auto;" value="{{ $record->expiry_date?->format('Y-m-d') }}">
                                    <input type="text" name="rejection_reason" class="form-control form-control-sm" placeholder="Rejection reason (if any)" value="{{ $record->rejection_reason }}">
                                    <input type="file" name="clearance_file" class="form-control form-control-sm">
                                    <button class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No protector records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $records->links() }}
    </div>

</div>

<div class="modal fade" id="newProtectorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('agency.protector.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Protector Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Candidate</label>
                    <select name="candidate_id" class="form-select" required>
                        <option value="">Select candidate</option>
                        @foreach($candidates as $c)
                            <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Submission Proof (optional)</label>
                    <input type="file" name="submission_file" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

@endsection
