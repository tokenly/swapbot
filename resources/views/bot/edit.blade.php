@extends('app')

@section('appjs')
<script src="/js/edit-bot.js"></script>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Edit your Swapbot</div>
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

                    <form class="form-horizontal" role="form" method="POST" action="/bot/edit">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <div class="form-group">
                            <label class="col-md-4 control-label">Bot Name</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">Bot Description</label>
                            <div class="col-md-6">
                                <textarea class="form-control" name="description" rows="6">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        @for ($asset_number = 1; $asset_number < 5; $asset_number++)

                        <div class="asset-group" data-asset-group="{{$asset_number}}"{!! $asset_number > 1 ? ' style="display: none;"' : '' !!}>
                            <hr>

                            <h4>Swap #{{ $asset_number }}</h4>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Receives Asset</label>
                                <div class="col-md-6">
                                    <input placeholder="BTC" type="text" class="form-control" name="asset_in_{{$asset_number}}" value="{{ old('asset_in_'.$asset_number) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Sends Asset</label>
                                <div class="col-md-6">
                                    <input placeholder="LTBCOIN" type="text" class="form-control" name="asset_out_{{$asset_number}}" value="{{ old('asset_out_'.$asset_number) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-4 control-label">Rate</label>
                                <div class="col-md-6">
                                    <input placeholder="0.99" type="number" step="any" min="0" class="form-control" name="vend_rate_{{$asset_number}}" value="{{ old('vend_rate_'.$asset_number) }}">
                                </div>
                            </div>
                        </div>
                        @endfor

                        <div class="form-group">
                            <div class="col-md-12">
                                <a href="#add" data-add-asset><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Another Asset</a>
                            </div>
                        </div>




                        <hr>
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Create
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
