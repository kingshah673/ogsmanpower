@extends(request()->is('agency/*') ? 'components.website.agency.layout.app' : 'components.website.company.layout.app')
@section('title', 'Nominated Workers')
@section('main')
@php
    $nwRoutePrefix = $nwRoutePrefix ?? 'company.nominated-workers';
    $countries = $countries ?? collect();
    $defaultDestinationCountry = $defaultDestinationCountry ?? default_destination_country_name();
    $selectedNationality = old('nationality');
    $selectedDestination = old('destination_country', $defaultDestinationCountry);
@endphp
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        <h3 class="mb-1">Nominated Workers</h3>
        <p class="text-muted">Batch workers pending visa — no login accounts. Only your uploads are visible here.</p>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Add worker</div>
                    <form method="POST" action="{{ route($nwRoutePrefix.'.store') }}" class="card-body">
                        @csrf
                        <div class="mb-2"><input name="full_name" class="form-control" placeholder="Full name *" required value="{{ old('full_name') }}"></div>
                        <div class="mb-2"><input name="passport_number" class="form-control" placeholder="Passport number" value="{{ old('passport_number') }}"></div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <select name="nationality" class="form-control">
                                    <option value="">Nationality</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->name }}" @selected((string) $selectedNationality === (string) $country->name)>
                                            {{ $country->name }}@if($country->short_name) ({{ $country->short_name }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-2"><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <x-forms.intl-phone-input
                                    name="phone"
                                    optional
                                    :value="old('phone')"
                                    placeholder="Phone"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-6 mb-2"><input name="email" type="email" class="form-control" placeholder="Email" value="{{ old('email') }}"></div>
                        </div>
                        <div class="mb-2">
                            <select name="destination_country" class="form-control">
                                <option value="">Destination country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->name }}" @selected((string) $selectedDestination === (string) $country->name)>
                                        {{ $country->name }}@if($country->short_name) ({{ $country->short_name }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><input name="job_title" class="form-control" placeholder="Job title" value="{{ old('job_title') }}"></div>
                        <button class="btn btn-primary btn-sm">Save worker</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">Batch CSV import</div>
                    <form method="POST" action="{{ route($nwRoutePrefix.'.import') }}" enctype="multipart/form-data" class="card-body">
                        @csrf
                        <p class="small text-muted">CSV headers: full_name, passport_number, nationality, date_of_birth, phone, email, destination_country, job_title</p>
                        <input type="file" name="batch_file" accept=".csv,text/csv" class="form-control mb-2" required>
                        <button class="btn btn-outline-primary btn-sm">Import</button>
                    </form>
                </div>
                <div class="card">
                    <div class="card-header">Upload documents (AI match)</div>
                    <form method="POST" action="{{ route($nwRoutePrefix.'.documents') }}" enctype="multipart/form-data" class="card-body">
                        @csrf
                        <input type="file" name="documents[]" class="form-control mb-2" multiple required>
                        <button class="btn btn-outline-success btn-sm">Upload &amp; match</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Passport</th>
                        <th>Destination</th>
                        <th>Visa context</th>
                        <th>Status</th>
                        <th>Docs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workers as $worker)
                        <tr>
                            <td>{{ $worker->full_name }}</td>
                            <td>{{ $worker->passport_number ?: '—' }}</td>
                            <td>{{ $worker->destination_country ?: '—' }}</td>
                            <td>
                                @if(in_array($worker->status, ['pending_docs','matched','pending_visa'], true))
                                    <span class="badge bg-warning text-dark">Pending visa</span>
                                @else
                                    <span class="badge bg-secondary">{{ str_replace('_',' ', $worker->status) }}</span>
                                @endif
                            </td>
                            <td>{{ str_replace('_',' ', $worker->status) }}</td>
                            <td>{{ $worker->documents_count }}</td>
                            <td><a href="{{ route($nwRoutePrefix.'.show', $worker) }}" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No nominated workers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $workers->links() }}</div>
    </div>
</div>
@endsection
