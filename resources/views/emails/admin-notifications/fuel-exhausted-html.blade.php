@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Your Swapbot Needs Some Fuel</h4>
<p>&nbsp;</p>
@stop

@section('main')

<p>Hi {{ $user['name'] }}.</p>

<p>Your <a href="{{ $botUrl }}">Swapbot named {{ $bot['name'] }}</a> is almost out of BTC fuel.  Swapbots need BTC fuel to send tokens and to pay miner's fees.</p>

<p>To fix this, please send 0.01 BTC to {{ $bot['address'] }} from one of your blacklisted bitcoin addresses ({{ $botBlacklist }}).</p>

<p>&nbsp;</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.com.</p>

<p>&nbsp;</p>

<small>To stop receiving these notifications, please <a href="{{ $updateProfileLink }}">update your communication preferences</a>.</small>

@stop