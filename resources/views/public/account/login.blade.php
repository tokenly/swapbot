@extends('public.base')

@section('header_content')
<h1>Swapbot Adminstration</h1>
@stop

@section('content')
    <h2>Login or Register</h2>

    <p>Welcome to the Swapbot administration control panel.</p>

    <p>You are not logged in now.  If you want to create or manage a Swapbot of your own, you will need to <a href="/account/authorize">login or register with Tokenpass</a> first.</p>

    <a href="/account/authorize" class="btn btn-primary">Login or Sign Up Now</a>

    <div class="spacer2"></div>

    <p>To purchase tokens from a Swapbot, no account is necessary.</p>

@stop
