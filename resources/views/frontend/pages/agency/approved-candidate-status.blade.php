{{-- @extends('backend.layouts.app') --}}
@extends('components.website.agency.layout.app')


@section('title')
    {{ __('candidate_list') }}
@endsection
@section('main')
    <div class="card shadow">
        <div class="card-header text-white d-flex justify-content-between align-items-center">
            <div class="input-group input-group-sm w-50">
                <input type="text" id="searchInput" class="form-control border rounded-start"
                    placeholder="{{ __('Search Candidates...') }}" onkeyup="filterTable()">
                <button class="btn btn-light border rounded-end" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center p-3">
            <div class="me-3">
                <img src="{{ $candidate->candidate->photo ?? 'https://via.placeholder.com/40' }}" alt="Photo"
                    class="rounded-circle shadow-sm" style="width: 50px; height: 50px;">
            </div>
            <div>
                <h6 class="mb-0">{{ $candidate->candidate->user->name }}</h6>
                <small class="text-muted">{{ $candidate->candidate->user->email ?? '' }}</small>
            </div>

        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless align-middle" id="candidatesTable">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-secondary">
                            <th class="text-start">{{ __('Date & Time') }}</th>
                            <th>{{ __('Status Name') }}</th>
                            <th>{{ __('Approved') }}</th>
                            <th>{{ __('Attachment') }}</th>


                        </tr>
                    </thead>
                    <tbody>
                        @if ($assignCandidates->count() > 0)
                            @foreach ($assignCandidates as $candidatee)
                                <tr>
                                    <td class="text-start align-middle">
                                        {{ $candidatee->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="fw-bold align-middle">{{ $candidatee->name ?? __('N/A') }}</td>
                                    <td class="fw-bold align-middle">
                                        {{ $candidatee->is_approved == 1 ? 'Yes' : 'No' }}
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('storage/' . $candidatee->attachments) }}" alt="Attachment"
                                                style="width: 100px; height: auto; object-fit: cover;" class="me-2">
                                            <a href="{{ asset('storage/' . $candidatee->attachments) }}"
                                                download="{{ basename($candidatee->attachments) }}"
                                                class="btn btn-sm btn-secondary">
                                                {{ __('Download') }}
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <span class="text-muted">{{ __('No Data found.') }}</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($assignCandidates->count())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $assignCandidates->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


@endsection
