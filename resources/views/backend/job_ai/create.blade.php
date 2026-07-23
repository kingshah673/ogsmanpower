@extends('backend.layouts.app')

@section('title','Create Job AI Template')

@section('content')

<style>
.card-ai {
    background:#fff;
    border-radius:12px;
    padding:20px;
    border:1px solid #e5e7eb;
    box-shadow:0 6px 20px rgba(0,0,0,0.04);
}

.form-label {
    font-weight:500;
    font-size:13px;
}

.btn-ai {
    background:#0a66c2;
    color:#fff;
    border:none;
    padding:10px;
    border-radius:8px;
}

.btn-ai:hover {
    background:#004182;
}
</style>

<div class="container-fluid">

<div class="row">
<div class="col-lg-8">

<div class="card-ai">

<h4 class="mb-4">🤖 Create Job AI Template</h4>

<form method="POST" action="{{ route('admin.job_ai.store') }}">
@csrf

{{-- JOB TITLE --}}
<div class="mb-3">
<label class="form-label">Job Title</label>

<select name="job_title" class="form-control" required>

    <option value="">Select Job Title</option>

    @foreach($jobCategories as $category)
        <option value="{{ $category->slug }}">
            {{ ucfirst(str_replace('-', ' ', $category->slug)) }}
        </option>
    @endforeach

</select>

</div>

{{-- DESCRIPTION --}}
<div class="mb-3">
<label class="form-label">AI Description</label>
<textarea name="description" id="descriptionEditor" class="form-control"></textarea>
</div>

{{-- SKILLS --}}
<div class="mb-3">
<label class="form-label">Skills (comma separated)</label>
<input type="text" name="skills_input" class="form-control" placeholder="Driving, GPS, Safety">
</div>

{{-- TAGS --}}
<div class="mb-3">
<label class="form-label">Tags (comma separated)</label>
<input type="text" name="tags_input" class="form-control" placeholder="Driver, Transport">
</div>

{{-- SALARY --}}
<div class="row">
<div class="col-md-6">
<label class="form-label">Min Salary</label>
<input type="number" name="min_salary" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Max Salary</label>
<input type="number" name="max_salary" class="form-control">
</div>
</div>

{{-- EXPERIENCE --}}
<div class="mt-3">
<label class="form-label">Experience (years)</label>
<input type="text" name="experience" class="form-control">
</div>

<button class="btn-ai w-100 mt-4">
Save Template
</button>

</form>

</div>

</div>
</div>

</div>


{{-- CKEDITOR --}}
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
ClassicEditor
.create(document.querySelector('#descriptionEditor'))
.then(editor => {
    window.editorInstance = editor;
});
</script>

@endsection