<!doctype html>
<?php 
$receipt = $swap['receipt']; 
$receipt_type = isset($receipt['type']) ? $receipt['type'] : null;
?>

<head>
    <meta charset="utf-8">
    <title>Swapbot | {{ $bot['name'] }} Swap Details</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="/css/{{$manifest('details.css')}}">
    <link rel="icon" href="{{ $bot->getRobohashURL() }}">
    @if ($bugsnag['apiKey'])
    <script src="//d2wy8f7a9ursnm.cloudfront.net/bugsnag-2.min.js" data-apikey="{{$bugsnag['apiKey']}}"></script>
    <script>Bugsnag.releaseStage = "{{$bugsnag['releaseStage']}}";</script>
    @endif
</head>

<body>
    <div class="swapbot-background"></div>
    <div class="swapbot-container">
        <div class="header">
            <div class="avatar">
                <a href="{{ $bot->getPublicBotURL() }}" title="Return to the bot home page"><img src="{{ $botRobohashUrl }}"></a>
            </div>
            <div class="status-dot bckg-{{ $fmt->swapStateDotColor($swap) }}"></div>
            <h1><a href="{{ $bot->getPublicBotURL() }}" target="_blank">{{ $bot['name'] }}</a></h1>
        </div>
        <div class="content">
            <div class="content-header">
                <div class="status-icon icon-{{ $fmt->buildStateIcon($swap) }}"></div>
                <div class="message">
                    @if ($swap['state'] == 'complete')
                        @if ($receipt_type == 'swap')
                            {{-- swap --}}
                            <span><b>Successfully</b> swapped <b>{{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}</b> for <b>{{ $currency($receipt['quantityOut']) }} {{ $receipt['assetOut'] }}</b>.</span>
                        @elseif ($receipt_type == 'refund')
                            {{-- refund --}}
                            <span>This swap was <b>refunded</b> <b>{{ $currency($receipt['quantityOut']) }} {{ $receipt['assetOut'] }}</b>.</span>
                        @else
                        @endif
                    @else
                        <span>This swap is {{ $fmt->formatState($swap['state']) }}.</span>
                    @endif
                </div>
            </div>
            <ul class="wide-list wide-list-short1">
                <li>
                    <div class="item-header">Status</div>
                    <p>{{ $fmt->formatState($swap['state']) }}</p>
                </li>
                <li>
                    <div class="item-header">Deposit Received</div>
                    <p>{{ $fmt->formatDate($swap['created_at']) }}</p>
                </li>
                <li>
                    <div class="item-header">Tokens Delivered</div>
                    <p>{{ $fmt->formatDate($swap['completed_at']) }}</p>
                </li>
                <li>
                    <div class="item-header">Amount</div>
                    <p>
                    @if (($receipt_type == 'refund'))
                        Received {{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }} and refunded
                        {{ $currency($receipt['quantityOut']) }} {{ $receipt['assetOut'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityOut'], $receipt['assetOut'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
                    @elseif (isset($receipt['assetIn']) AND isset($receipt['assetOut']))
                        {{ $currency($receipt['quantityIn']) }} {{ $receipt['assetIn'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityIn'], $receipt['assetIn'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
                        →
                        {{ $currency($receipt['quantityOut']) }} {{ $receipt['assetOut'] }}{{ $fmt->fiatSuffix($strategy, $receipt['quantityOut'], $receipt['assetOut'], isset($receipt['conversionRate']) ? $receipt['conversionRate'] : null) }}
                    @else
                        <span class="none">none</span>
                    @endif
                </li>

                @if (isset($receipt['changeOut']) AND $receipt['changeOut'] > 0)
                <li>
                    <div class="item-header">Change</div>
                    <p>
                        {{ $currency($receipt['changeOut']) }} {{ isset($receipt['changeOutAsset']) ? $receipt['changeOutAsset'] : 'BTC' }} in change
                    </p>
                </li>
                @endif

                <li>
                    <div class="item-header">Recipient's address</div>
                    <p>
                        @if (isset($receipt['destination']))
                            <a href="{{ $fmt->formatAddressHref($receipt['destination']) }}" target="_blank">{{ $receipt['destination'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Swapbot's address</div>
                    <p>
                        <a href="{{ $fmt->formatAddressHref($bot['address']) }}" target="_blank">{{ $bot['address'] }}</a>
                    </p>
                </li>
                <li>
                    <div class="item-header">Incoming Transaction ID</div>
                    <p>
                        @if (isset($receipt['txidIn']))
                            <a href="{{ $fmt->formatBlockchainHref($receipt['txidIn'], $receipt['assetIn']) }}" target="_blank">{{ $receipt['txidIn'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Outgoing Transaction ID</div>
                    <p>
                        @if (isset($receipt['txidOut']))
                            <a href="{{ $fmt->formatBlockchainHref($receipt['txidOut'], $receipt['assetOut']) }}" target="_blank">{{ $receipt['txidOut'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
            </ul>
        </div>
        <div class="footer">powered by <a href="http://swapbot.tokenly.com/" target="_blank">Swapbot</a></div>
    </div>

{{-- Scripts --}}
<script src="/js/public/{{$manifest('asyncLoad.js')}}"></script>
<script>
    window.asyncLoad("//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", "css");
    window.asyncLoad("//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700", "css");
</script>
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
@if ($env == 'production')
<script src="/static/js/react-0.13.3.min.js"></script>
@else
<script src="https://fb.me/react-0.13.3.js"></script>
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


@include('partials.tawk')


</body>

</html>
