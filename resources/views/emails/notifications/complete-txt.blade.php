@extends('emails.base.base-txt')

@section('main')

Hello Again!


The tokens you recently purchased from {{ $bot['name'] }} have been delivered.

When you’re ready, log into your wallet to use, send or redeem them as you see fit.

To recap your order, you sent {{ $bot['name'] }} {{ $inQty }} {{ $inAsset }} and we’ve just sent you {{ $outQty }} {{ $outAsset }}{{ $hasChange ? " along with {$swap['receipt']['changeOut']} {$inAsset} in change" : ''}}.


What Happens Next?


That’s it!  You can make a new purchase if you’d like.  And thanks for using Swapbot, if you’d like to create your own automated multi-token vending machine in just a few minutes, visit {{ $host }}.

If you have any questions or comments about your experience please email the team@tokenly.co


@stop