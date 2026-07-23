@extends('components.website.agency.layout.app')

@section('title', __('Visa Delay Forecast'))

@section('main')
<div class="container-fluid mt-4">

    <div class="mb-3">
        <h4 class="mb-0"><i class="ph-clock-countdown text-primary"></i> Visa Delay Forecast</h4>
        <p class="text-muted small mb-0">
            Estimated completion dates based on how long each step has historically taken across all visa cases.
            Global average per step: {{ $global_average_days }} days.
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job / Country</th>
                        <th>Current Step</th>
                        <th>Days on Step</th>
                        <th>Est. Days Remaining</th>
                        <th>Est. Completion</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forecasts as $item)
                        @php $case = $item['case']; @endphp
                        <tr>
                            <td>{{ optional($case->candidate?->user)->name ?? '—' }}</td>
                            <td>{{ $case->job->title ?? '—' }} <br><small class="text-muted">{{ $case->country_name ?? '' }}</small></td>
                            <td>{{ $item['active_step'] }}</td>
                            <td>{{ $item['days_on_active_step'] }}</td>
                            <td>{{ $item['estimated_days_remaining'] }}</td>
                            <td>{{ $item['estimated_completion_date'] }}</td>
                            <td>
                                @if($item['at_risk'])
                                    <span class="badge bg-danger">At risk</span>
                                @else
                                    <span class="badge bg-success">On track</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No visa cases currently in progress.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    @if(!empty($step_averages))
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h6 class="mb-0">Historical Average Duration Per Step</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Step</th><th>Average Days</th></tr></thead>
                <tbody>
                    @foreach($step_averages as $name => $avg)
                        <tr><td>{{ $name }}</td><td>{{ $avg }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
