<!doctype html>

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
                <img src="http://robohash.org/siemano.png?set=set3">
            </div>
            <div class="status-dot bckg-{{ $swapFormatter->stateDotColor($swap['state']) }}"></div>
            <h1><a href="http://raburski.com/swapbot0" target="_blank">The Sample LTBCOIN Bot</a></h1>
        </div>
        <div class="content">
            <div class="content-header">
                <div class="status-icon icon-{{ $swapFormatter->stateIcon($swap['state']) }}"></div>
                <div class="message">
                    @if ($swap['state'] == 'complete')
                        <span><b>Successfully</b> completed exchange of <b>{{ $swap['receipt']['quantityIn'] }} {{ $swap['receipt']['assetIn'] }}</b> for <b>{{ $swap['receipt']['quantityOut'] }} {{ $swap['receipt']['assetOut'] }}</b>.</span>
                    @else
                        <span>This swap is {{ $swapFormatter->formatState($swap['state']) }}.</span>
                    @endif
                </div>
            </div>
            <ul class="wide-list">
                <li>
                    <div class="item-header">Status</div>
                    <p>{{ $swapFormatter->formatState($swap['state']) }}</p>
                </li>
                <li>
                    <div class="item-header">Date</div>
                    <p>{{ $swapFormatter->formatDate($swap['updated_at']) }}</p>
                </li>
                <li>
                    <div class="item-header">Amount</div>
                    <p>
                    @if (isset($swap['assetIn']) AND isset($swap['assetOut']))
                        {{ $swap['receipt']['quantityIn'] }} {{ $swap['receipt']['assetIn'] }}
                        →
                        {{ $swap['receipt']['quantityOut'] }} {{ $swap['receipt']['assetOut'] }}
                    @else
                        <span class="none">none</span>
                    @endif
                </li>
                <li>
                    <div class="item-header">Recipient's address</div>
                    <p>
                        @if (isset($swap['receipt']['destination']))
                        {{ $swap['receipt']['destination'] }}
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Swapbot's address</div>
                    <p>{{ $bot['address'] }}</p>
                </li>
                <li>
                    <div class="item-header">Incoming TXID</div>
                    <p>
                        @if (isset($swap['receipt']['txidIn']))
                            <a href="#" target="_blank">{{ $swap['receipt']['txidIn'] }}</a>
                        @else
                            <span class="none">none</span>
                        @endif
                    </p>
                </li>
                <li>
                    <div class="item-header">Outgoing TXID</div>
                    <p>
                        @if (isset($swap['receipt']['txidOut']))
                            <a href="#" target="_blank">{{ $swap['receipt']['txidOut'] }}</a>
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
<script src="http://fb.me/react-0.13.2.js"></script>
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
