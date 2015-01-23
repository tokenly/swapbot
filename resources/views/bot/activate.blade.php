@extends('app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Activate Swapbot {{$bot['name']}}</div>
                <div class="panel-body">
                    @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form class="form-horizontal" role="form" method="POST" action="/bot/activate/{{$bot['uuid'] === null ? 'new' : $bot['uuid']}}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <p>Activating this bot will generate an address and begin watching for transactions.</p>

                        <div style="margin-top: 4rem;">
                            <button type="submit" class="btn btn-success">
                                Activate Bot
                            </button>
                            <a href="/bot/show/{{$bot['uuid']}}" class="btn btn-default pull-right">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
