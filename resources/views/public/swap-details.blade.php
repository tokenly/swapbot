<!doctype html>
<?php 
$receipt = $swap['receipt']; 
$receipt_type = isset($receipt['type']) ? $receipt['type'] : null;
?>

<head>
    <meta charset="utf-8">
    <title>Swapbot | Transaction details</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="/css/details.css">
</head>

<body>
    <div class="swapbot-background"></div>
    <div class="swapbot-container">
        <div class="header">
            <div class="avatar">
                <img src="http://robohash.org/{{ $bot['hash'] }}.png?set=set3">
            </div>
            <div class="status-dot bckg-{{ $swapFormatter->swapStateDotColor($swap) }}"></div>
            <h1><a href="{{ $bot->getPublicBotURL() }}" target="_blank">{{ $bot['name'] }}</a></h1>
        </div>
        <div class="content">
            <div class="content-header">
                <div class="status-icon icon-{{ $swapFormatter->buildStateIcon($swap) }}"></div>
                <div class="message">
                    @if ($swap['state'] == 'complete')
                        @if ($receipt_type == 'swap')
                            {{-- swap --}}
                            <span><b>Successfully</b> swapped <b>{{ $receipt['quantityIn'] }} {{ $receipt['assetIn'] }}</b> for <b>{{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}</b>.</span>
                        @elseif ($receipt_type == 'refund')
                            {{-- refund --}}
                            <span>This swap was <b>refunded</b> <b>{{ $receipt['quantityOut'] }} {{ $receipt['assetOut'] }}</b>.</span>
                        @else
                        @endif
                    @else
                        <span>This swap is {{ $swapFormatter->formatState($swap['state']) }}.</span>
                    @endif
                </div>
            </div>
            <ul class="wide-list wide-list-short1">
                <li>
                    <div class="item-header">Status</div>
                    <p>{{ $swapFormatter->formatState($swap['state']) }}</p>
                </li>
                <li>
                    <div class="item-header">Deposit Recieved</div>
                    <p>{{ $swapFormatter->formatDate($swap['created_at']) }}</p>
                </li>
                <li>
                    <div class="item-header">Tokens Delivered</div>
                    <p>{{ $swapFormatter->formatDate($swap['completed_at']) }}</p>
                </li>
                <li>
                    <div class="item-header">Amount</div>
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
                </li>
                <li>
                    <div class="item-header">Recipient's address</div>
                    <p>
                        @if (isset($receipt['destination']))
                            <a href="{{ $swapFormatter->formatAddressHref($receipt['destination']) }}" target="_blank">{{ $receipt['destination'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Swapbot's address</div>
                    <p>
                        <a href="{{ $swapFormatter->formatAddressHref($bot['address']) }}" target="_blank">{{ $bot['address'] }}</a>
                    </p>
                </li>
                <li>
                    <div class="item-header">Incoming TXID</div>
                    <p>
                        @if (isset($receipt['txidIn']))
                            <a href="{{ $swapFormatter->formatBlockchainHref($receipt['txidIn'], $receipt['assetIn']) }}" target="_blank">{{ $receipt['txidIn'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Outgoing TXID</div>
                    <p>
                        @if (isset($receipt['txidOut']))
                            <a href="{{ $swapFormatter->formatBlockchainHref($receipt['txidOut'], $receipt['assetOut']) }}" target="_blank">{{ $receipt['txidOut'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
            </ul>
        </div>
        <div class="footer">powered by <a href="http://swapbot.co/" target="_blank">Swapbot</a></div>
    </div>

{{-- Scripts --}}
<script src="/js/public/asyncLoad.js"></script>
<script>
    window.asyncLoad("//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", "css");
    window.asyncLoad("//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700", "css");
</script>
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
@if ($env == 'production')
<script src="http://fb.me/react-0.13.3.min.js"></script>
@else
<script src="http://fb.me/react-0.13.3.js"></script>
@endif
<script src="/bower_components/moment/min/moment.min.js"></script>

{{-- pusher --}}
<script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
<script src="{{$pusherUrl}}/public/client.js"></script>

{{-- app --}}
{{-- 
<script src="/js/swap/swap-combined.js"></script>
<script>SwapApp.init({!! json_encode($bot->serializeForAPI('public'), JSON_HEX_APOS) !!})</script>
 --}}



</body>

</html>
