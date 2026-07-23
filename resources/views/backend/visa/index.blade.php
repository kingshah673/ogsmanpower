@extends('backend.layouts.app')

@section('title','Job AI Templates')

@section('content')
<h2>Visa Cases</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Country</th>
        <th>Status</th>
    </tr>

    @foreach($cases as $case)
        <tr>
            <td>{{ $case->id }}</td>
            <td>{{ $case->country }}</td>
            <td>{{ $case->status }}</td>
        </tr>
    @endforeach
</table>
@endsection