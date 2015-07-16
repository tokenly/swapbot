@extends('emails.base.base-txt')

@section('main')
<?php $receipt = $swap['receipt']; $receipt_type = (isset($receipt['type']) ? $receipt['type'] : null); ?>
<?php $swapFormatter = app('Swapbot\Models\Formatting\SwapFormatter'); ?>

Hello Again!


The tokens you recently purchased from {{ $bot['name'] }} have been delivered.

When you’re ready, log into your wallet to use, send or redeem them as you see fit.

To recap your order, you sent {!! $botLink !!} {{ $swapFormatter->formatCurrency($inQty) }} {{ $inAsset }} and we’ve just sent you {{ $swapFormatter->formatCurrency($outQty) }} {{ $outAsset }}{{ $hasChange ? " along with ".$swapFormatter->formatCurrency($swap['receipt']['changeOut'])." {$inAsset} in change" : ''}}.



Your Swap Receipt

Status
  {{ $swapFormatter->formatState($swap['state']) }}

Deposit Recieved
  {{ $swapFormatter->formatDate($swap['createdAt']) }}

Tokens Delivered
  {{ $swapFormatter->formatDate($swap['completedAt']) }}

Amount
@if (($receipt_type == 'refund'))
  Received {{ $receipt['quantityIn'] }} {{ $receipt['assetIn'] }}{{ $swapFormatter->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }} and refunded
  {{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}{{ $swapFormatter->fiatSuffix($strategy, $receipt['quantityOut'], $receipt['assetOut'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
@elseif (isset($receipt['assetIn']) AND isset($receipt['assetOut']))
  {{ $receipt['quantityIn'] }} {{ $receipt['assetIn'] }}{{ $swapFormatter->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
  →
  {{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}{{ $swapFormatter->fiatSuffix($strategy, $receipt['quantityOut'], $receipt['assetOut'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
@else
  none
@endif

@if (isset($receipt['changeOut']) AND $receipt['changeOut'] > 0)
Change
  {{ $swapFormatter->formatCurrency($receipt['changeOut']) }} {{ isset($receipt['changeOutAsset']) ? $receipt['changeOutAsset'] : 'BTC' }} in change
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