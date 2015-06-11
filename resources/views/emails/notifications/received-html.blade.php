@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Hello Again!</h4>
<p>&nbsp;</p>
@stop


@section('main')

<p>Your recent order from {{ $bot['name'] }} has been received!  In fact, we’re sending out your tokens right now!</p>

<p>To recap your order, you sent {{ $bot['name'] }} {{ $inQty }} {{ $inAsset }} and we’ve just sent you {{ $outQty }} {{ $outAsset }}{{ $hasChange ? " along with {$swap['receipt']['changeOut']} {$inAsset} in change" : ''}}.</p>

<p>&nbsp;</p>
<h4>What Happens Next?</h4>
<p>&nbsp;</p>

<p>You’ll receive a third and final email once your tokens are ready and waiting for you.  They might be there already but sometimes it takes the bitcoin network as much as an hour if the miners run into a really tricky block.</p>

<p>No need to refresh your wallet.  We’ll email you once we see they’ve safely arrived and are ready for you to use.</p>

<p>That’s it!  If you have any questions or comments email the team@tokenly.co</p>


@stop