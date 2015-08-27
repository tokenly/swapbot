@extends('public.base')

@section('header_content')
<h1>My Email Preferences</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                @include('public.account.includes.sidebar')
            </div>
            <div class="col-md-9">
                <h2>Email Communications</h2>

                <div class="spacer1"></div>

                <p>Thanks.  Your Email preferences are updated.</p>

                <div class="spacer2"></div>

                <a href="/account/welcome" class="btn btn-default">Return to Home</a>

            </div>
        </div>
    </div>


@stop
