@extends('layouts.agent-clean')

@section('content')

<div class="container">

    <h3 class="mb-4">My Applications</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-bordered align-middle">

                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Status</th>
                        <th>Interview</th>
                        <th>Visa</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($applications as $app)

                <tr>
                    <td>{{ $app->candidate->first_name ?? '' }}</td>
                    <td>{{ $app->job->title ?? '' }}</td>

                    <td>
                        <span class="badge bg-info">{{ $app->status }}</span>
                    </td>

                    <td>
                        {{ $app->interview_date ?? '-' }}
                    </td>

                    <td>
                        {{ $app->visa_status ?? '-' }}
                    </td>

                    <td>

                        {{-- STATUS --}}
                        <form method="POST" action="{{ route('agent.applications.status',$app->id) }}">
                            @csrf

                            <select name="status" class="form-control mb-1">
                                <option value="pending">Pending</option>
                                <option value="interview">Interview</option>
                                <option value="selected">Selected</option>
                                <option value="deployed">Deployed</option>
                                <option value="rejected">Rejected</option>
                            </select>

                            <button class="btn btn-sm btn-primary w-100">Update</button>
                        </form>

                        {{-- INTERVIEW --}}
                        <form method="POST" action="{{ route('agent.interview',$app->id) }}" class="mt-2">
                            @csrf
                            <input type="date" name="interview_date" class="form-control mb-1">
                            <input type="text" name="interview_location" placeholder="Location" class="form-control mb-1">
                            <button class="btn btn-sm btn-warning w-100">Set Interview</button>
                        </form>

                        {{-- VISA --}}
                        <form method="POST" action="{{ route('agent.visa',$app->id) }}" class="mt-2">
                            @csrf
                            <select name="visa_status" class="form-control mb-1">
                                <option value="processing">Processing</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <button class="btn btn-sm btn-success w-100">Update Visa</button>
                        </form>

                    </td>

                </tr>

                @empty
                <tr>
                    <td colspan="6" class="text-center">No applications</td>
                </tr>
                @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>

@endsection