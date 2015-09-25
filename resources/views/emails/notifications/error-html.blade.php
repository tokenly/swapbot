@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Oops. There was a problem.</h4>
<p>&nbsp;</p>
@stop


@section('main')
<?php $receipt = $swap['receipt']; $receipt_type = (isset($receipt['type']) ? $receipt['type'] : null); ?>


<p>Hi there! You recently tried to place an order from {!! $botLink !!} and your deposit of {{ $currency($inQty) }} {{ $inAsset }} was received.  But there was an unexpected problem when trying to send you your {{ $currency($outQty) }} {{ $outAsset }}{{ $hasChange ? " along with ".$currency($swap['receipt']['changeOut'])." {$inAsset} in change" : ''}}.
</p>

<p>To resolve this issue, you will need to contact customer support at team@tokenly.com.</p>

<p>Sorry for the inconvenience.</p>



<p style="height: 2px;">&nbsp;</p>
<h4>Your Swap Details</h4>
<p>&nbsp;</p>

<p><strong>Deposit Received</strong></p>
<p>{{ $fmt->formatDate($swap['createdAt']) }}</p>

<p><strong>Amount</strong></p>
<p>
    Received {{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
</p>

<p><strong>Recipient's address</strong></p>
<p>
    @if (isset($receipt['destination']))
        <a href="{{ $fmt->formatAddressHref($receipt['destination']) }}" target="_blank">{{ $receipt['destination'] }}</a>
    @else
        <span class="none">none</span>
    @endif
</p>

<p><strong>Swapbot's address</strong></p>
<p>
    <a href="{{ $fmt->formatAddressHref($bot['address']) }}" target="_blank">{{ $bot['address'] }}</a>
</p>

<p><strong>Incoming Transaction ID</strong></p>
<p>
    @if (isset($receipt['txidIn']))
        <a href="{{ $fmt->formatBlockchainHref($receipt['txidIn'], $receipt['assetIn']) }}" target="_blank">{{ $receipt['txidIn'] }}</a>
    @else
        <span class="none">none</span>
    @endif
</p>
  
<p><strong>Swap Reference ID</strong></p>
<p>{{ $swap['id'] }}</p>


<p style="height: 12px;">&nbsp;</p>
<hr />
<p>&nbsp;</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.com.</p>


@stop