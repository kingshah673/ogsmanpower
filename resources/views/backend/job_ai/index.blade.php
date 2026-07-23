@extends('backend.layouts.app')

@section('title','Job AI Templates')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between mb-3">
    <h4>Job AI Templates</h4>
    <a href="{{ route('admin.job_ai.create') }}" class="btn btn-primary">
        + Add New
    </a>
</div>

<table class="table table-bordered">

<thead>
<tr>
    <th>ID</th>
    <th>Job Title</th>
    <th>Salary</th>
    <th>Experience</th>
</tr>
</thead>

<tbody>
@foreach($data as $item)
<tr>
    <td>{{ $item->id }}</td>
    <td>{{ $item->job_title }}</td>
    <td>{{ $item->min_salary }} - {{ $item->max_salary }}</td>
    <td>{{ $item->experience }}</td>

    <td>

        {{-- EDIT --}}
        <a href="{{ route('admin.job_ai.edit', $item->id) }}"
           class="btn btn-sm btn-primary">
            <i class="fas fa-edit"></i>
        </a>

        {{-- DELETE --}}
        <form action="{{ route('admin.job_ai.delete', $item->id) }}"
              method="POST"
              style="display:inline-block"
              onsubmit="return confirm('Are you sure?')">

            @csrf
            @method('DELETE')

            <button class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i>
            </button>
        </form>

    </td>
</tr>
@endforeach
</tbody>

</table>

</div>

@endsection