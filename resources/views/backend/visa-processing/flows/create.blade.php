@extends('backend.layouts.app')
@section('title', 'Create Visa Flow')
@section('content')
<div class="container-fluid">
    <div class="card" style="max-width:640px;">
        <div class="card-header"><h3 class="card-title mb-0">New country flow</h3></div>
        <form method="POST" action="{{ route('admin.visa-flows.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Country *</label>
                    <select name="search_country_id" class="form-control" required>
                        <option value="">Select a country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('search_country_id') === (string) $country->id)>
                                {{ $country->name }}@if($country->short_name) ({{ $country->short_name }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('search_country_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label>Visa type (optional)</label>
                    <input type="text" name="visa_type" class="form-control" value="{{ old('visa_type') }}">
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" checked>
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Create</button>
                <a href="{{ route('admin.visa-flows.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
