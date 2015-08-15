@extends('public.base')

@section('header_content')
<h1>Authorization Failed</h1>
@stop

@section('content')
    <div class="alert alert-danger">
        <p>This login was not successful.</p>
        <p>{{$error_msg}}</p>
    </div>

    <p><a href="/">Return home</a></p>
@stop
