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

                @include('partials.errors', ['errors' => $errors])

                <div class="spacer1"></div>

                <form method="POST" action="/account/emails">

                    {!! csrf_field() !!}

                    <h4>Check Each Box to Subscribe to Email Events</h4>

                    @foreach ($user->getAllEmailPreferenceTypes() as $type_info)
                    <div class="spacer1"></div>
                    <div class="checkbox">
                        <label>
                            <input class="checkbox-input" type="checkbox" name="{{ $type_info['name'] }}" id="checkbox_{{ $type_info['name'] }}"{{ old('prefs')[$type_info['name']] ? 'checked="checked"' : '' }} value="1">
                            <div class="checkbox-label">
                                <div>Email Me About {{ $type_info['label'] }}</div>
                                <small class="text-muted">{{ $type_info['subtitle'] }}</small>
                            </div>
                        </label>
                    </div>
                    @endforeach


                    <div class="spacer3"></div>

                    <div>
                        <a href="/account/welcome" class="btn btn-default pull-right">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>

                </form>

            </div>
        </div>
    </div>


@stop
