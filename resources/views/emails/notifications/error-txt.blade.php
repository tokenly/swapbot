@extends('emails.base.base-txt')

@section('main')
<?php $receipt = $swap['receipt']; $receipt_type = (isset($receipt['type']) ? $receipt['type'] : null); ?>


Hello Again!


Hi there! You recently tried to place an order from {!! $botLink !!} and your deposit of {{ $currency($inQty) }} {{ $inAsset }} was received.  But there was an unexpected problem when trying to send you your {{ $currency($outQty) }} {{ $outAsset }}{{ $hasChange ? " along with ".$currency($swap['receipt']['changeOut'])." {$inAsset} in change" : ''}}.

To resolve this issue, you will need to contact customer support at team@tokenly.com.

Sorry for the inconvenience.

Your Swap Details



Deposit Received
  {{ $fmt->formatDate($swap['createdAt']) }}

Amount
  Received {{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}

Recipient's address
@if (isset($receipt['destination']))
  {{ $receipt['destination'] }}
@else
  none
@endif

Swapbot's address
  {{ $bot['address'] }}

Incoming Transaction ID
@if (isset($receipt['txidIn']))
  {{ $receipt['txidIn'] }}
@else
  none
@endif

Swap Reference ID
  {{ $swap['id'] }}


If you have any questions or comments about your experience please email the team@tokenly.com.



@stop