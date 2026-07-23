{{-- @extends('backend.layouts.app') --}}
@extends('components.website.agency.layout.app')
@section('title')
    {{ __('candidate_list') }}
@endsection

@section('main')
    <div class="card shadow">
        <div class="card-header  text-white d-flex justify-content-between align-items-center">

            <div class="input-group input-group-sm w-50">
                <input type="text" id="searchInput" class="form-control border rounded-start"
                    placeholder="{{ __('Search Candidates...') }}" onkeyup="filterTable()">
                <button class="btn btn-light border rounded-end" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>

        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless align-middle" id="candidatesTable">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-secondary">
                            <th class="text-start">{{ __('Candidate') }}</th>
                            <th>{{ __('Job Title') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($candidates->count() > 0)
                            @foreach ($candidates as $candidate)
                                <tr>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="{{ $candidate->candidate->photo ?? 'https://via.placeholder.com/40' }}"
                                                    alt="Photo" class="rounded-circle shadow-sm"
                                                    style="width: 50px; height: 50px;">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $candidate->candidate->user->name }}</h6>
                                                <small
                                                    class="text-muted">{{ $candidate->candidate->user->email ?? '' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-bold">{{ $candidate->job->title ?? __('N/A') }}</td>

                                    <td>
                                        <a
                                            href="{{ route('agency.approvedCandidateStatus', ['candidate_id' => $candidate->candidate_id, 'job_id' => $candidate->job_id]) }}">
                                            <span
                                                class="badge bg-success rounded-pill px-3 py-1">{{ __('Details') }}</span>
                                        </a>
                                    </td>


                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <span class="text-muted">{{ __('No candidates found.') }}</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if ($candidates->count())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $candidates->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('candidatesTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                rows[i].style.display = found ? '' : 'none';
            }
        }
    </script>

@endsection
