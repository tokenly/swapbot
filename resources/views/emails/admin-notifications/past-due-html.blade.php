@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Your Swapbot Payment is Past Due</h4>
<p>&nbsp;</p>
@stop

@section('main')

<p>Hi {{ $user['name'] }}.</p>

<p>Your <a href="{{ $botUrl }}">Swapbot named {{ $bot['name'] }}</a> is now unpaid and is not processing any swaps.</p>

<p>To fix this, please vist our <a href="{{ $adminUrl }}">Swapbot administration app</a> and follow the instructions there to make a payment.</p>

<p>&nbsp;</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.com.</p>

<small>To stop receiving these notifications, please <a href="{{ $updateEmailPrefsLink }}">update your email preferences</a>.</small>

@stop