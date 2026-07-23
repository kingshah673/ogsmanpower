@extends('components.website.agency.new-sidebar')

@section('main')

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Document Checklist — {{ $candidate->first_name }} {{ $candidate->last_name }}</h4>
            <small class="text-muted">Review uploaded documents, set approval status, and track expiry dates.</small>
        </div>
        <a href="{{ route('agency.candidates.edit', $candidate->id) }}" class="btn btn-light">&larr; Back to candidate</a>
    </div>

    @if(! $document)
        <div class="alert alert-warning">This candidate has not uploaded any documents yet.</div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Note</th>
                            <th style="width: 260px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checklist as $key => $label)
                            @php
                                $filePath = $document->{$key} ?? null;
                                $status = $document->statusFor($key);
                                $note = $document->noteFor($key);
                                $expiry = $document->expiryFor($key);
                                $badgeClass = match ($status) {
                                    'approved' => 'badge bg-success',
                                    'rejected' => 'badge bg-danger',
                                    'pending' => 'badge bg-warning text-dark',
                                    default => 'badge bg-secondary',
                                };
                                $isExpiringSoon = $expiry && $expiry->isFuture() && $expiry->diffInDays(now()) <= 30;
                                $isExpired = $expiry && $expiry->isPast();
                            @endphp
                            <tr>
                                <td><strong>{{ $label }}</strong></td>
                                <td>
                                    @if($filePath)
                                        <a href="{{ asset($filePath) }}" target="_blank" rel="noopener">View file</a>
                                    @else
                                        <span class="text-muted">Not uploaded</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $badgeClass }}">{{ $status ? ucfirst($status) : 'Not uploaded' }}</span>
                                </td>
                                <td>
                                    @if($expiry)
                                        <span class="{{ $isExpired ? 'text-danger fw-bold' : ($isExpiringSoon ? 'text-warning fw-bold' : '') }}">
                                            {{ $expiry->format('d M Y') }}
                                        </span>
                                        @if($isExpired)
                                            <div class="small text-danger">Expired</div>
                                        @elseif($isExpiringSoon)
                                            <div class="small text-warning">Expiring soon</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $note ?: '—' }}</td>
                                <td>
                                    @if($filePath)
                                        <form method="POST" action="{{ route('agency.candidates.documents.status', $candidate->id) }}" class="d-flex flex-column gap-1">
                                            @csrf
                                            <input type="hidden" name="doc_key" value="{{ $key }}">
                                            <div class="d-flex gap-1">
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="pending" @selected($status === 'pending')>Pending</option>
                                                    <option value="approved" @selected($status === 'approved')>Approved</option>
                                                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                                                </select>
                                                @if(in_array($key, ['medical', 'police_character_certificate'], true))
                                                    <input type="date" name="expiry_date" class="form-control form-control-sm" value="{{ $expiry?->format('Y-m-d') }}">
                                                @endif
                                            </div>
                                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Note (optional)" value="{{ $note }}">
                                            <button class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Nothing to review</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @if($candidate->passport_expiry_date)
                            <tr>
                                <td><strong>Passport expiry (profile)</strong></td>
                                <td colspan="4">
                                    @php
                                        $pExpiry = \Illuminate\Support\Carbon::parse($candidate->passport_expiry_date);
                                        $pExpired = $pExpiry->isPast();
                                        $pSoon = ! $pExpired && $pExpiry->diffInDays(now()) <= 30;
                                    @endphp
                                    <span class="{{ $pExpired ? 'text-danger fw-bold' : ($pSoon ? 'text-warning fw-bold' : '') }}">
                                        {{ $pExpiry->format('d M Y') }}
                                    </span>
                                    @if($pExpired)
                                        <span class="badge bg-danger ms-2">Expired</span>
                                    @elseif($pSoon)
                                        <span class="badge bg-warning text-dark ms-2">Expiring soon</span>
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    @endif

</div>

@endsection
