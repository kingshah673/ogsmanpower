@extends('backend.layouts.app')
@section('title')
    Agent / Facilitator List
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title line-height-36">Agent / Facilitator</h3>
                            <div>
                                <a href="{{ route('agent.create') }}" class="btn bg-primary">
                                    <i class="fas fa-plus mr-1"></i> {{ __('create') }}
                                </a>
                                @if (request('keyword') || request('ev_status') || request('sort_by'))
                                    <a href="{{ route('agent.index') }}" class="btn bg-danger">
                                        <i class="fas fa-times"></i>&nbsp; {{ __('clear') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('agent.index') }}" method="GET" onchange="this.submit();">
                        <div class="card-body border-bottom row">
                            <div class="col-xl-4 col-md-6 col-12">
                                <label>{{ __('search') }}</label>
                                <input name="keyword" type="text" placeholder="Search Agent / Facilitator" class="form-control" value="{{ request('keyword') }}">
                            </div>
                            <div class="col-xl-3 col-md-6 col-12">
                                <label>{{ __('email_verification') }}</label>
                                <select name="ev_status" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    <option value="true" @selected(request('ev_status') === 'true')>{{ __('verified') }}</option>
                                    <option value="false" @selected(request('ev_status') === 'false')>{{ __('not_verified') }}</option>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-6 col-12">
                                <label>{{ __('sort_by') }}</label>
                                <select name="sort_by" class="form-control">
                                    <option value="latest" @selected(request('sort_by', 'latest') === 'latest')>Latest</option>
                                    <option value="oldest" @selected(request('sort_by') === 'oldest')>Oldest</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div class="card-body table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Agent / Facilitator</th>
                                    <th>Parent Agency</th>
                                    <th>Workers</th>
                                    <th>{{ __('account') }} {{ __('status') }}</th>
                                    <th>{{ __('email_verification') }}</th>
                                    <th width="18%">{{ __('action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agents as $agent)
                                    <tr>
                                        <td>
                                            <strong>{{ $agent->name }}</strong><br>
                                            <small class="text-muted">{{ $agent->email }}</small>
                                        </td>
                                        <td>{{ $agent->parentAgencyUser?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $agent->candidates_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <label class="switch">
                                                <input data-id="{{ $agent->id }}" type="checkbox"
                                                    class="success status-switch"
                                                    {{ (int) $agent->status === 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <label class="switch">
                                                <input data-userid="{{ $agent->id }}" type="checkbox"
                                                    class="success email-verification-switch"
                                                    {{ $agent->email_verified_at ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <a href="{{ route('agent.show', $agent->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="{{ route('agent.edit', $agent->id) }}" class="btn btn-sm btn-outline-info">Edit</a>
                                            <a href="{{ route('agent.candidates', $agent->id) }}" class="btn btn-sm btn-outline-success">Workers</a>
                                            <form action="{{ route('agent.destroy', $agent->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this Agent / Facilitator?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No Agent / Facilitators found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">{{ $agents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .switch { position: relative; display: inline-block; width: 35px; height: 19px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
        .slider:before { position: absolute; content: ""; height: 15px; width: 15px; left: 3px; bottom: 2px; background-color: white; transition: .4s; }
        input.success:checked+.slider { background-color: #28a745; }
        input:checked+.slider:before { transform: translateX(15px); }
        .slider.round { border-radius: 34px; }
        .slider.round:before { border-radius: 50%; }
    </style>
@endsection

@section('script')
    <script>
        $('.status-switch').on('change', function() {
            var status = $(this).prop('checked') == true ? 1 : 0;
            var id = $(this).data('id');
            $.ajax({
                type: "GET",
                dataType: "json",
                url: '{{ route('agent.status.change') }}',
                data: { status: status, id: id },
                success: function(response) { toastr.success(response.message, 'Success'); }
            });
        });
        $('.email-verification-switch').on('change', function() {
            var status = $(this).prop('checked') == true ? 1 : 0;
            var id = $(this).data('userid');
            $.ajax({
                type: "GET",
                dataType: "json",
                url: '{{ route('agent.verify.change') }}',
                data: { status: status, id: id },
                success: function(response) { toastr.success(response.message, 'Success'); }
            });
        });
    </script>
@endsection
