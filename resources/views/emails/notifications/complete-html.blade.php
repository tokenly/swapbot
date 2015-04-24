@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Hello Again!</h4>
<p>&nbsp;</p>
@stop


@section('main')

<p>The tokens you recently purchased from {{ $bot['name'] }} have been delivered.</p>

<p>When you’re ready, log into your wallet to use, send or redeem them as you see fit.</p>

<p>To recap your order, you sent {{ $bot['name'] }} {{ $inQty }} {{ $inAsset }} and we’ve just sent you {{ $outQty }} {{ $outAsset }}.</p>

<p>&nbsp;</p>
<p>What Happens Next?</p>
<p>&nbsp;</p>

<p>That’s it!  You can make a new purchase if you’d like.  And thanks for using Swapbot, if you’d like to create your own automated multi-token vending machine in just a few minute visit {{ $host }}.</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.co</p>


@stop