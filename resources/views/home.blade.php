@extends('app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">My Dashboad</div>

                <div class="panel-body">
                    <h3>My Swapbots</h3>

                    @if (count($bots))
                    <ul class="list-unstyled">
                        @foreach ($bots as $bot)
                        <li class="botlink">
                            {{-- {{$bot['active'] ? 'active' : 'inactive'}} --}}
                            <div>

                                <a href="/bot/show/{{$bot['uuid']}}">
                                    <span class="glyphicon {{$bot['active'] ? ' glyphicon-ok' : 'glyphicon-warning-sign'}}"></span>
                                    {{$bot['name'] ?: 'Bot '.$bot['uuid'] }}
                                </a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="no-bots">
                        You don't have any bots yet.
                    </div>
                    @endif

                    <div>
                        <p style="margin-top: 4rem;">
                            <a href="/bot/edit/new" class="button btn btn-primary">Create a Bot</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
