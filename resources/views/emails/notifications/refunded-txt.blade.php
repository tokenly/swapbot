@extends('emails.base.base-txt')

@section('main')
<?php $receipt = $swap['receipt']; $receipt_type = (isset($receipt['type']) ? $receipt['type'] : null); ?>


Hello Again!


Hi there! You recently tried to place an order from {!! $bot['name'] !!} ({!! $botLink !!}) and your deposit of {{ $currency($inQty) }} {{ $inAsset }} has been refunded. Why? {{ $refundReason }}.

The next time you check your wallet, you'll find your deposit of {{ $currency($inQty) }} {{ $inAsset }}, available for your use (less any bitcoin miner's fee).

Sorry for the inconvenience.


Your Refund Receipt

Deposit Received
  {{ $fmt->formatDate($swap['createdAt']) }}

Tokens Delivered
  {{ $fmt->formatDate($swap['completedAt']) }}

Amount
@if (($receipt_type == 'refund'))
  Received {{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }} and refunded
  {{ $currency($receipt['quantityOut']) }} {{ $receipt['assetOut'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityOut'], $receipt['assetOut'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
@else
  none
@endif

@if (isset($receipt['changeOut']) AND $receipt['changeOut'] > 0)
Change
  {{ $currency($receipt['changeOut']) }} {{ isset($receipt['changeOutAsset']) ? $receipt['changeOutAsset'] : 'BTC' }} in change
@endif

Recipient's address
@if (isset($receipt['destination']))
  {{ $receipt['destination'] }}
@else
  none
@endif

Swapbot's address
  {{ $bot['address'] }}

Incoming TXID
@if (isset($receipt['txidIn']))
  {{ $receipt['txidIn'] }}
@else
  none
@endif

Outgoing TXID
@if (isset($receipt['txidOut']))
  {{ $receipt['txidOut'] }}
@else
  none
@endif



What Happens Next?


That’s it!  You can make a new purchase if you’d like.  And thanks for using Swapbot, if you’d like to create your own automated multi-token vending machine in just a few minutes, visit {{ $host }}.

If you have any questions or comments about your experience please email the team@tokenly.com.


@stop