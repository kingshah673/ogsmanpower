@extends('components.website.agency.layout.app')

@section('title', __('Commissions'))

@section('main')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Commissions</h4>
        <a href="{{ route('agency.commissions.export') }}" class="btn btn-sm btn-outline-success">
            <i class="ph-download-simple"></i> Export Excel
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h3 class="mb-0">{{ number_format($totals['pending'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Approved</h6>
                    <h3 class="mb-0">{{ number_format($totals['approved'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Paid</h6>
                    <h3 class="mb-0">{{ number_format($totals['paid'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <label class="mb-0 mr-2">Filter status:</label>
                <select name="status" class="form-control form-control-sm" style="max-width: 200px;" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') == 'approved')>Approved</option>
                    <option value="paid" @selected(request('status') == 'paid')>Paid</option>
                </select>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Company</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Paid On</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $commission)
                        <tr>
                            <td>#{{ $commission->id }}</td>
                            <td>{{ optional($commission->candidate?->user)->name ?? '—' }}</td>
                            <td>{{ $commission->job->title ?? '—' }}</td>
                            <td>{{ optional($commission->appliedJob?->job?->company?->user)->name ?? '—' }}</td>
                            <td>{{ $commission->currency }} {{ number_format($commission->amount, 2) }}</td>
                            <td><span class="{{ $commission->badgeClass() }}">{{ ucfirst($commission->status) }}</span></td>
                            <td>{{ optional($commission->created_at)->format('d M Y') }}</td>
                            <td>{{ optional($commission->paid_at)->format('d M Y') ?? '—' }}</td>
                            <td><a href="{{ route('agency.commissions.receipt', $commission->id) }}" class="btn btn-sm btn-outline-secondary">PDF</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No commissions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $commissions->links() }}
    </div>

</div>
@endsection
