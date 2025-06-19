@extends('catalog::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('catalog.name') !!}</p>
@endsection
