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
                        <dd><span class="inactive">Inactive</span></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <a href="/bot/edit/{{ $bot['id'] }}" class="button btn btn-primary">Edit This Bot</a>
</div>
@endsection
