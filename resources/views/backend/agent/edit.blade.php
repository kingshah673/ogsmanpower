@extends('backend.layouts.app')
@section('title')
    Edit Agent / Facilitator
@endsection

@section('content')
    <div class="container-fluid">
        <form class="form-horizontal" action="{{ route('agent.update', $agent->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title line-height-36">Edit Agent / Facilitator</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">{{ __('account_details') }}</div>
                        <div class="card-body">
                            <div class="form-group">
                                <x-forms.label name="name" :required="true" />
                                <x-forms.input type="text" name="name" data-show-errors="true" placeholder="name" value="{{ old('name', $agent->name) }}" />
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <x-forms.label name="username" :required="false" />
                                    <x-forms.input type="text" name="username" placeholder="username" value="{{ old('username', $agent->username) }}" />
                                </div>
                                <div class="form-group col-sm-6">
                                    <x-forms.label name="email" :required="true" />
                                    <x-forms.input type="email" name="email" placeholder="email" value="{{ old('email', $agent->email) }}" />
                                </div>
                            </div>
                            <div class="form-group">
                                <x-forms.label name="password" :required="false" />
                                <x-forms.input type="password" name="password" placeholder="leave blank to keep" />
                            </div>
                            <div class="form-group">
                                <label>Parent Recruitment Agency</label>
                                <select name="agency_id" class="form-control">
                                    <option value="">— Select agency —</option>
                                    @foreach ($agencies as $agency)
                                        <option value="{{ $agency->id }}" @selected(old('agency_id', $agent->agency_id) == $agency->id)>
                                            {{ $agency->name }} ({{ $agency->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="d-flex align-items-center">
                                    <input type="checkbox" name="status" value="1" {{ old('status', $agent->status) ? 'checked' : '' }} class="mr-2">
                                    Active account
                                </label>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('agent.index') }}" class="btn btn-secondary">{{ __('cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
