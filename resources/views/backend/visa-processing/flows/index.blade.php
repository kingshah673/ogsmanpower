@extends('backend.layouts.app')
@section('title', 'Visa Processing Flows')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Visa Processing CMS — Country Flows</h3>
            <a href="{{ route('admin.visa-flows.create') }}" class="btn btn-primary btn-sm">Add Country Flow</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Visa type</th>
                        <th>Steps</th>
                        <th>Publish</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flows as $flow)
                        <tr>
                            <td>{{ $flow->country_name }}</td>
                            <td>{{ $flow->visa_type ?: '—' }}</td>
                            <td>{{ $flow->steps_count }}</td>
                            <td>
                                <span class="badge badge-{{ ($flow->publish_status ?? 'published') === 'published' ? 'success' : 'secondary' }}">
                                    {{ $flow->publish_status ?? 'published' }}
                                </span>
                                <small>v{{ $flow->version ?? 1 }}</small>
                            </td>
                            <td>{{ $flow->is_active ? 'Yes' : 'No' }}</td>
                            <td><a href="{{ route('admin.visa-flows.edit', $flow) }}" class="btn btn-sm btn-info">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center p-4">No flows yet. Create one for a destination country.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($flows->hasPages())
            <div class="card-footer">{{ $flows->links() }}</div>
        @endif
    </div>
</div>
@endsection
