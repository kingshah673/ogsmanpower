@extends('components.website.agency.new-sidebar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@section('main')

<div class="container-fluid mt-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">My Candidates</h4>

        <a href="{{ route('agency.candidates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Candidate
        </a>
    </div>

    {{-- CARD --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Candidate</th>
                        <th>Passport</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($candidates as $c)
                    <tr>

                        {{-- PROFILE --}}
                        <td>
                            <div class="d-flex align-items-center">

                                {{-- PHOTO --}}
                                <div class="me-2">
                                    @if($c->photo)
                                        <img src="{{ asset('storage/'.$c->photo) }}"
                                             class="rounded-circle"
                                             width="40" height="40">
                                    @else
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                             style="width:40px;height:40px;">
                                            {{ strtoupper(substr($c->first_name,0,1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- NAME --}}
                                <div>
                                    <strong>{{ $c->first_name }} {{ $c->last_name }}</strong><br>
                                    <small class="text-muted">{{ $c->whatsapp_number ?? '' }}</small>
                                </div>

                            </div>
                        </td>

                        {{-- PASSPORT --}}
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ $c->passport_number ?? 'N/A' }}
                            </span>
                        </td>

                        {{-- STATUS --}}
                        <td>
                            @if($c->status == 'available')
                                <span class="badge bg-success">Available</span>
                            @elseif($c->status == 'not_available')
                                <span class="badge bg-danger">Not Available</span>
                            @else
                                <span class="badge bg-secondary">{{ $c->status }}</span>
                            @endif
                        </td>

                        {{-- ACTION --}}
                        <td class="text-end">

                            <a href="{{ route('agency.candidates.edit',$c->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>

                            <a href="{{ route('agency.candidates.documents',$c->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="Document checklist">
                                <i class="fas fa-file-alt"></i>
                            </a>

                            <a href="{{ route('agency.candidates.delete',$c->id) }}"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete this candidate?')">
                                <i class="fas fa-trash"></i>
                            </a>

                        </td>

                    </tr>
                    @empty

                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-users fa-2x mb-2 text-muted"></i>
                            <p class="mb-0">No candidates found</p>
                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>

@endsection