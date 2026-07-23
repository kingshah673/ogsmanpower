@extends('components.website.agent.new-sidebar')

@section('main')
<div class="container py-4">
    <h4 class="fw-bold mb-1">Account Progress</h4>
    <p class="text-muted mb-4">Complete your agent profile and track your workers.</p>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center">
                <h2 class="fw-bold text-primary mb-0">{{ $pct }}%</h2>
                <p class="text-muted mb-0">Profile complete</p>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-semibold mb-3">Checklist</h6>
                <ul class="list-unstyled mb-0">
                    <li>{{ $fields['name'] ? '✅' : '⬜' }} Name</li>
                    <li>{{ $fields['email'] ? '✅' : '⬜' }} Email</li>
                    <li>{{ $fields['whatsapp'] ? '✅' : '⬜' }} WhatsApp</li>
                    <li>{{ $fields['image'] ? '✅' : '⬜' }} Profile photo</li>
                    <li>{{ $fields['agency_linked'] ? '✅' : '⬜' }} Linked to parent agency</li>
                </ul>
                <a href="{{ route('agent.setting') }}" class="btn btn-primary btn-sm mt-3">Complete settings</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><strong>{{ $stats['workers'] }}</strong> workers registered</div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><strong>{{ $stats['selected'] }}</strong> selected</div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><strong>{{ $stats['pending'] }}</strong> in pipeline</div></div>
    </div>
</div>
@endsection
