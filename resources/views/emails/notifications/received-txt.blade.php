@extends('emails.base.base-txt')

@section('main')

Hello Again!

Your recent order from {{ $bot['name'] }} has been received!  In fact, we’re sending out your tokens right now!

To recap your order, you sent {{ $bot['name'] }} {{ $inQty }} {{ $inAsset }} and we’ve just sent you {{ $outQty }} {{ $outAsset }}.

What Happens Next?

You’ll receive a third and final email once your tokens are ready and waiting for you.  They might be there already but sometimes it takes the bitcoin network as much as an hour if the miners run into a really tricky block.

No need to refresh your wallet.  We’ll email you once we see they’ve safely arrived and are ready for you to use.

That’s it!  If you have any questions or comments email the team@tokenly.co

@stop