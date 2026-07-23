@extends('backend.layouts.app')
@section('title', 'Visa Cases')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Visa Processing Management</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Candidate</th>
                        <th>Company</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td>{{ $case->id }}</td>
                            <td>{{ $case->candidate?->user?->name ?? ('#'.$case->candidate_id) }}</td>
                            <td>{{ $case->company?->user?->name ?? $case->company_id }}</td>
                            <td>{{ $case->country_name }}</td>
                            <td><span class="badge badge-secondary">{{ $case->status }}</span></td>
                            <td>{{ $case->progressPercent() }}%</td>
                            <td><a class="btn btn-sm btn-info" href="{{ route('admin.visa-cases.show', $case) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center p-4">No cases yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cases->hasPages())
            <div class="card-footer">{{ $cases->links() }}</div>
        @endif
    </div>
</div>
@endsection
