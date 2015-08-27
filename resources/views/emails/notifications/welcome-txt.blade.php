@extends('emails.base.base-txt')

@section('main')



Thanks for making a purchase with SwapBot!

We can see your payment on the blockchain and will be sending your {{ $outAsset }} shortly (After {{ $bot['confirmationsRequired'] }} confirmations or about {{ $bot['confirmationsRequired'] * 10 }} minutes).

To recap your order, you sent {{ $bot['name'] }} {{ $currency($inQty) }} {{ $inAsset }} and will be receiving {{ $currency($outQty) }} {{ $outAsset }}{{ $hasChange ? " along with ".$currency($swap['receipt']['changeOut'])." {$inAsset} in change" : ''}} shortly.

What Happens Next?

You’ll receive a second email when your payment has been successfully confirmed by the Bitcoin network, which will trigger your Swapbot to send out your tokens.  You should get this email in under thirty minutes.

When your tokens have been delivered and are ready to use, we’ll send you one last email letting you know they’re waiting in your wallet.

That’s it!  If you have any questions or comments email the team@tokenly.com.


If you did not leave your email address after making a purchase at swapbot.tokenly.com please use this link to unsubscribe:

{{ $unsubscribeLink }}


@stop