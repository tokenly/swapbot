@extends('public.base')

@section('header_content')
<h1>Welcome to Swapbot</h1>
<p>By
    <a href="http://tokenly.com">Tokenly</a>
</p>

@stop


@section('content')

<div class="container">
    <div class="row">

        <div class="col-md-6">
            <div class="panel panel-default panel-home">
                <div class="panel-heading">
                    <h4>Swap and Redeem Tokens</h4>
                </div>
                <div class="panel-body">
                    <p>Check out the
                        <a href="https://letstalkbitcoin.com/services">services directory at LetsTalkBitcoin.com</a>
                        for a list of active swapbots and a list of merchants accepting tokens for goods and services.
                    </p>
                    <div class="spacer2"></div>
                    <p>
                        <a class="btn btn-primary" href="https://letstalkbitcoin.com/services">Find Tokens and Services</a>
                    </p>
                </div>
            </div>
        </div>


        <div class="col-md-6">
            <div class="panel panel-default panel-home">
                <div class="panel-heading">
                    <h4>Create Your Own Swapbot</h4>
                </div>
                <div class="panel-body">



                    <p>Swapbots allow you to offer your own tokens for exchange to users who want them. Create a
                        <a href="{{$tokenlyAccountsSiteUrl}}">Tokenly Account</a> and set up your
                        <a href="/account/login">own Swapbot</a>.</p>
                    <div class="spacer2"></div>

                    <p>
                        <a class="btn btn-success" href="/account/login">Swapbot Administration</a>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

@stop
