@extends('public.base')

@section('header_content')
<h1>Logging In</h1>
@stop

@section('content')
    <h2>Hello {{$user['name']}}</h2>

    <p>Please wait a moment while we log you in...</p>

    <script>
    window.localStorage.setItem("apiToken", '{!! $user['apitoken'] !!}');
    window.localStorage.setItem("apiSecretKey", '{!! $user['apisecretkey'] !!}');
    window.localStorage.setItem("user", '{!! json_encode($user->serializeForAPI(), JSON_HEX_APOS) !!}');

    setTimeout(function() { window.location.href = '/account/welcome'; }, 750);
    </script>

@stop
