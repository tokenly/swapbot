@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Thanks for making a purchase with SwapBot!</h4>
<p>&nbsp;</p>
@stop

@section('main')


<p>We can see your payment on the blockchain and will be sending your {{ $outAsset }} shortly (After {{ $bot['confirmationsRequired'] }} confirmations or about {{ $bot['confirmationsRequired'] * 10 }} minutes).</p>

<p>To recap your order, you sent {{ $bot['name'] }} {{ $inQty }} {{ $inAsset }} and will be receiving {{ $outQty }} {{ $outAsset }}{{ $hasChange ? " along with {$swap['receipt']['changeOut']} {$inAsset} in change" : ''}} shortly.</p>

<p>&nbsp;</p>
<h4>What Happens Next?</h4>
<p>&nbsp;</p>

<p>You’ll receive a second email when your payment has been successfully confirmed by the Bitcoin network, which will trigger your Swapbot to send out your tokens.  You should get this email in under thirty minutes.</p>

<p>When your tokens have been delivered and are ready to use, we’ll send you one last email letting you know they’re waiting in your wallet.</p>

<p>That’s it!  If you have any questions or comments email the team@tokenly.co.</p>

<p>&nbsp;</p>

<small>If you did not leave your email address after making a purchase at swapbot.co please click <a href="{{ $unsubscribeLink }}">here to unsubscribe</a>.</small>

@stop