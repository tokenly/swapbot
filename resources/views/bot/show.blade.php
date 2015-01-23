@extends('app')

@section('appjs')
<script src="/js/edit-bot.js"></script>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{ $bot['name'] }}</div>
                <div class="panel-body">
                    <dl>
                        <dt>Description</dt>
                        <dd>{{ $bot['description'] }}</dd>

                        <dt>Swaps</dt>
                        <dd>@include('bot.includes.swapslist', ['swaps' => $bot['swaps']])</dd>

                        <dt>Status</dt>
                        <dd>
                            @if ($bot['active'])
                            <span class="active"><span class="glyphicon glyphicon-ok"></span> Active</span>
                            @else
                            <span class="inactive"><span class="glyphicon glyphicon-warning-sign"></span> Inactive</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            <a href="/bot/edit/{{ $bot['id'] }}" class="button btn btn-primary">Edit This Bot</a>
            @if (!$bot['active'])
            <a href="/bot/activate/{{ $bot['id'] }}" class="button btn btn-success">Activate This Bot</a>
            @endif
        </div>
    </div>
</div>
@endsection
