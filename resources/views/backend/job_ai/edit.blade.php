@extends('backend.layouts.app')

@section('title','Edit AI Template')

@section('content')

<div class="container-fluid">

<div class="card p-4">

<h4>Edit Template</h4>

<form method="POST" action="{{ route('admin.job_ai.update',$data->id) }}">
@csrf

<input name="job_title" class="form-control mb-2"
       value="{{ $data->job_title }}">

<textarea name="description" class="form-control mb-2">
{{ $data->description }}
</textarea>

<input name="skills_input" class="form-control mb-2"
value="{{ implode(',', $data->skills ?? []) }}">

<input name="tags_input" class="form-control mb-2"
value="{{ implode(',', $data->tags ?? []) }}">

<input name="min_salary" class="form-control mb-2"
value="{{ $data->min_salary }}">

<input name="max_salary" class="form-control mb-2"
value="{{ $data->max_salary }}">

<input name="experience" class="form-control mb-2"
value="{{ $data->experience }}">

<button class="btn btn-success w-100">
Update Template
</button>

</form>

</div>

</div>

@endsection