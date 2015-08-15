@extends('public.base')

@section('header_content')
<h1>Logged Out</h1>
@stop

@section('content')
    <h2>You are logged out.</h2>

    <p>We will return you to the front page in a couple of seconds.</p>

    <script>
    window.localStorage.removeItem("apiToken");
    window.localStorage.removeItem("apiSecretKey");
    window.localStorage.removeItem("user");

    setTimeout(function() { window.location.href = '/'; }, 2000);
    </script>
@stop
