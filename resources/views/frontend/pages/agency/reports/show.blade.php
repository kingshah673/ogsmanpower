@extends('components.website.agency.layout.app')

@section('title', $report['title'] ?? __('Report'))

@section('main')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('agency.reports.index') }}" class="text-muted small"><i class="ph-arrow-left"></i> Back to Reports</a>
            <h4 class="mb-0 mt-1">{{ $report['title'] }}</h4>
        </div>
        <a href="{{ route('agency.reports.export', $type) }}" class="btn btn-sm btn-outline-success">
            <i class="ph-download-simple"></i> Export Excel
        </a>
    </div>

    @if(!empty($report['summary']))
        <div class="row g-3 mb-4">
            @foreach($report['summary'] as $label => $value)
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1 small">{{ $label }}</h6>
                            <h4 class="mb-0">{{ is_numeric($value) ? number_format($value, 2) : $value }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        @foreach($report['headings'] as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['headings']) }}" class="text-center text-muted py-4">No data available for this report yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>
@endsection
