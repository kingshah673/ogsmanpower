@extends('backend.layouts.app')

@section('title', 'Passport OCR Review')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Passport OCR Review</h5>
        <div>
            <a href="{{ request()->fullUrlWithQuery(['status' => '']) }}" class="btn btn-sm btn-outline-secondary">All</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_review']) }}" class="btn btn-sm btn-warning">Pending</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'confirmed']) }}" class="btn btn-sm btn-success">Confirmed</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected']) }}" class="btn btn-sm btn-danger">Rejected</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @forelse($logs as $log)
    <div class="card mb-4 shadow-sm border-{{ $log->status === 'confirmed' ? 'success' : ($log->status === 'rejected' ? 'danger' : 'warning') }}">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Log #{{ $log->id }}</strong>
                &nbsp;|&nbsp;
                Candidate:
                <a href="{{ route('candidate.show', $log->candidate_id) }}" target="_blank">
                    {{ $log->candidate?->user?->name ?? 'Unknown (#'.$log->candidate_id.')' }}
                </a>
                &nbsp;|&nbsp;
                <span class="badge bg-{{ $log->status === 'confirmed' ? 'success' : ($log->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                    {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                </span>
            </div>
            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
        </div>

        <div class="card-body">

            {{-- Conflict Alert --}}
            @if(!empty($log->conflicts))
            <div class="alert alert-danger py-2 mb-3">
                <strong>⚠️ Conflicts detected</strong> — OCR data differs from existing records.
                Please review before confirming.
            </div>
            @endif

            <form action="{{ route('admin.passport-ocr.confirm', $log->id) }}" method="POST">
                @csrf

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th style="width:22%">Field</th>
                                <th style="width:28%">OCR Extracted</th>
                                <th style="width:25%">Existing in DB</th>
                                <th style="width:25%">Apply Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $fields = [
                                'passport_number' => 'Passport Number',
                                'surname'         => 'Surname',
                                'given_names'     => 'Given Names',
                                'nationality'     => 'Nationality',
                                'date_of_birth'   => 'Date of Birth',
                                'gender'          => 'Gender',
                                'place_of_birth'  => 'Place of Birth',
                                'date_of_issue'   => 'Date of Issue',
                                'date_of_expiry'  => 'Date of Expiry',
                                'place_of_issue'  => 'Place of Issue',
                                'mrz_line1'       => 'MRZ Line 1',
                                'mrz_line2'       => 'MRZ Line 2',
                            ];
                            @endphp

                            @foreach($fields as $key => $label)
                            @php
                            $ocrVal      = $log->extracted_fields[$key] ?? null;
                            $dbVal       = $log->existing_db_fields[$key] ?? null;
                            $hasConflict = isset($log->conflicts[$key]);
                            @endphp
                            <tr class="{{ $hasConflict ? 'table-danger' : '' }}">
                                <td>{{ $label }}{{ $hasConflict ? ' ⚠️' : '' }}</td>
                                <td><code>{{ $ocrVal ?? '—' }}</code></td>
                                <td><code>{{ $dbVal ?? '—' }}</code></td>
                                <td>
                                    <input
                                        type="text"
                                        name="fields[{{ $key }}]"
                                        value="{{ $ocrVal ?? '' }}"
                                        class="form-control form-control-sm{{ $hasConflict ? ' border-danger' : '' }}"
                                        {{ $log->status !== 'pending_review' ? 'disabled' : '' }}
                                    >
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Raw OCR Text --}}
                <div class="mb-3">
                    <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                       href="#raw-{{ $log->id }}">Show Raw OCR Text</a>
                    <div class="collapse mt-2" id="raw-{{ $log->id }}">
                        <pre class="bg-light p-2 rounded" style="font-size:0.75rem;max-height:200px;overflow-y:auto">{{ $log->raw_ocr_text }}</pre>
                    </div>
                </div>

                @if($log->status === 'pending_review')
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm">
                        ✅ Confirm &amp; Apply
                    </button>
                    <a href="{{ route('admin.passport-ocr.reject', $log->id) }}"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Reject this OCR scan?')">
                        ❌ Reject
                    </a>
                </div>
                @else
                    <p class="text-muted mb-0 small">
                        Actioned by Admin #{{ $log->confirmed_by }} on {{ $log->confirmed_at?->format('d M Y H:i') }}
                    </p>
                @endif

            </form>
        </div>
    </div>
    @empty
        <div class="alert alert-info">No OCR logs found.</div>
    @endforelse

    <div class="d-flex justify-content-center">
        {{ $logs->withQueryString()->links() }}
    </div>

</div>
@endsection
