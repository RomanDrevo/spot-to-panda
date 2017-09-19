@extends('layouts.app')

@section('content')
    <search-customers :campaigns="{{ $campaigns }}" :countries="{{ $countries }}" :employees="{{ $employees }}" >
    </search-customers>
@endsection
