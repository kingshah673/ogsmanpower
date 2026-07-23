@extends('backend.layouts.app')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Agent / Facilitator Workers</h3>
            <small class="text-muted">
                Showing workers of <strong>{{ $agent->name }}</strong>
            </small>
        </div>

        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            ← Back
        </a>
    </div>

    {{-- STATS --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h5 class="text-muted">Total Workers</h5>
                    <h2 class="fw-bold">{{ method_exists($candidates, 'total') ? $candidates->total() : $candidates->count() }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Candidate</th>
                            <th>Contact</th>
                            <th>Passport</th>
                            <th>Status</th>
                            <th width="150">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($candidates as $index => $c)
                        <tr>

                            <td>{{ $index + 1 }}</td>

                            {{-- NAME --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    
                                    <img src="{{ $c->photo ? asset($c->photo) : asset('backend/image/default.png') }}"
                                         width="40" height="40"
                                         class="rounded-circle">

                                    <div>
                                        <strong>{{ $c->first_name }} {{ $c->last_name }}</strong><br>
                                        <small class="text-muted">{{ $c->cnic_number }}</small>
                                    </div>

                                </div>
                            </td>

                            {{-- CONTACT --}}
                            <td>
                                <small>
                                    📞 {{ $c->whatsapp_number }} <br>
                                    📍 {{ $c->district ?? '-' }}
                                </small>
                            </td>

                            {{-- PASSPORT --}}
                            <td>
                                <span class="fw-semibold">
                                    {{ $c->passport_number ?? '-' }}
                                </span>
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @if($c->status == 'available')
                                    <span class="badge bg-success">Available</span>
                                @elseif($c->status == 'not_available')
                                    <span class="badge bg-danger">Not Available</span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        {{ $c->status }}
                                    </span>
                                @endif
                            </td>

                            {{-- ACTION --}}
                            <td>
                                <div class="d-flex gap-2">

                                    <a href="{{ route('agent.candidates.edit', $c->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>

                                    <a href="{{ route('agent.candidates.delete', $c->id) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this candidate?')">
                                        Delete
                                    </a>

                                </div>
                            </td>

                        </tr>
                        @empty

                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p>No candidates found</p>
                                </div>
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

@endsection