@extends('public.base')

@section('page_title')
    Oh oh.  There was a problem.
@stop

@section('content')
    {{ $error }}
    <p><a href="/">Return home</a></p>
@stop