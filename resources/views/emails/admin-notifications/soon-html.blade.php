@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Your Swapbot Will Expire In A Week</h4>
<p>&nbsp;</p>
@stop

@section('main')

<p>Hi {{ $user['name'] }}.</p>

<p>Your <a href="{{ $botUrl }}">Swapbot named {{ $bot['name'] }}</a> will expire in a week.  After that, this Swapbot will not be able to process any swaps.</p>

<p>Please vist our <a href="{{ $adminUrl }}">Swapbot administration app</a> and follow the instructions there to make a payment before this Swapbot expires.</p>

<p>&nbsp;</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.com.</p>

<small>To stop receiving these notifications, please <a href="{{ $updateProfileLink }}">update your communication preferences</a>.</small>

@stop