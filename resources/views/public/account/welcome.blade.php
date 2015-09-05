@extends('public.base')

@section('header_content')
<h1>My Swapbot Account</h1>
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

                <p>You are signed in as <span class="username">{{$user['username']}}</span>.</p>

                <div class="spacer1"></div>

                <p>To manage your Swapbots or create a new one, please proceed to your <a href="/admin">Swapbot Dashboard</a>.</p>

                <div class="spacer2"></div>

                <a href="/admin" class="btn btn-success">Go to My Swapbot Dashboard</a>

            </div>
        </div>
    </div>


@stop
