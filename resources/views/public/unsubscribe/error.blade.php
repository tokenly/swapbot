@extends('public.base')

@section('header_content')
<h1>Oh oh.  There was a problem.</h1>
@stop

@section('content')
    {{ $error }}
    <p><a href="/">Return home</a></p>
@stop