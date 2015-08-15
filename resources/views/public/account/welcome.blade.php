@extends('public.base')

@section('header_content')
<h1>Swapbot Adminstration</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                @include('public.account.includes.sidebar')
            </div>
            <div class="col-md-9">
                <h2>Hello {{$user['name']}}</h2>

                <div class="spacer1"></div>

                <p>You have successfully signed in as user <span class="username">{{$user['username']}}</span>.  To manage your Swapbots or create a new one, please proceed to your <a href="/admin">Swapbot Dashboard</a>.</p>

                <div class="spacer1"></div>

                <a href="/admin" class="btn btn-success">Go to My Swapbot Dashboard</a>

                <div class="spacer4"></div>

                <p>To edit your account settings, visit your <a href="{{$tokenlyAccountsUrl}}">Tokenly Accounts profile</a> or you can <a href="/account/logout">Logout</a>.</p>
            </div>
        </div>
    </div>


@stop
