@extends('emails.base.base-txt')

@section('main')

Your Swapbot Needs Some Fuel


Hi {{ $user['name'] }}.

Your Swapbot named {{ $bot['name'] }} is almost out of BTC fuel.  Swapbots need BTC fuel to send tokens and to pay miner's fees.

To fix this, please send 0.01 BTC to {{ $bot['address'] }} from one of your blacklisted bitcoin addresses ({{ $botBlacklist }}).


If you have any questions or comments about your experience please email the team@tokenly.com.


To stop receiving these notifications, please update your email preferences at {{ $updateEmailPrefsLink }}.


@stop