@extends('emails.base.base-bot-image-html')

@section('subheaderTitle')
<h4>Hello Again!</h4>
<p>&nbsp;</p>
@stop


@section('main')

<p>The tokens you recently purchased from {!! $botLink !!} have been delivered.</p>

<p>When you’re ready, log into your wallet to use, send or redeem them as you see fit.</p>

<p>To recap your order, you sent {!! $botLink !!} {{ $inQty }} {{ $inAsset }} and we’ve just sent you {{ $outQty }} {{ $outAsset }}{{ $hasChange ? " along with {$swap['receipt']['changeOut']} {$inAsset} in change" : ''}}.</p>


<p style="height: 12px;">&nbsp;</p>
<hr />
<p style="height: 2px;">&nbsp;</p>
<h4>Your Swap Receipt</h4>
<p>&nbsp;</p>

<?php $receipt = $swap['receipt']; $receipt_type = (isset($receipt['type']) ? $receipt['type'] : null); ?>
<?php $swapFormatter = app('Swapbot\Models\Formatting\SwapFormatter'); ?>

<p><strong>Status</strong></p>
<p>{{ $swapFormatter->formatState($swap['state']) }}</p>

<p><strong>Deposit Recieved</strong></p>
<p>{{ $swapFormatter->formatDate($swap['createdAt']) }}</p>

<p><strong>Tokens Delivered</strong></p>
<p>{{ $swapFormatter->formatDate($swap['completedAt']) }}</p>

<p><strong>Amount</strong></p>
<p>
@if (($receipt_type == 'refund'))
    {{-- expr --}}
    Received {{ $receipt['quantityIn'] }} {{ $receipt['assetIn'] }} and refunded
    {{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}
@elseif (isset($receipt['assetIn']) AND isset($receipt['assetOut']))
    {{ $receipt['quantityIn'] }} {{ $receipt['assetIn'] }}
    →
    {{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}
@else
    <span class="none">none</span>
@endif

<p><strong>Recipient's address</strong></p>
<p>
    @if (isset($receipt['destination']))
        <a href="{{ $swapFormatter->formatAddressHref($receipt['destination']) }}" target="_blank">{{ $receipt['destination'] }}</a>
    @else
        <span class="none">none</span>
    @endif
</p>

<p><strong>Swapbot's address</strong></p>
<p>
    <a href="{{ $swapFormatter->formatAddressHref($bot['address']) }}" target="_blank">{{ $bot['address'] }}</a>
</p>

<p><strong>Incoming TXID</strong></p>
<p>
    @if (isset($receipt['txidIn']))
        <a href="{{ $swapFormatter->formatBlockchainHref($receipt['txidIn'], $receipt['assetIn']) }}" target="_blank">{{ $receipt['txidIn'] }}</a>
    @else
        <span class="none">none</span>
    @endif
</p>

<p><strong>Outgoing TXID</strong></p>
<p>
    @if (isset($receipt['txidOut']))
        <a href="{{ $swapFormatter->formatBlockchainHref($receipt['txidOut'], $receipt['assetOut']) }}" target="_blank">{{ $receipt['txidOut'] }}</a>
    @else
        <span class="none">none</span>
    @endif
</p>


<p style="height: 12px;">&nbsp;</p>
<hr />
<p style="height: 2px;">&nbsp;</p>
<h4>What Happens Next?</h4>
<p>&nbsp;</p>

<p>That’s it!  You can make a new purchase if you’d like.  And thanks for using Swapbot, if you’d like to create your own automated multi-token vending machine in just a few minutes, visit {{ $host }}.</p>

<p>If you have any questions or comments about your experience please email the team@tokenly.com.</p>


@stop