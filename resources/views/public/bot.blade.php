<!doctype html>

<head>
    <meta charset="utf-8">
    <title>Swapbot | {{ $bot['name'] }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link href="/css/main.css" rel="stylesheet">
</head>

<body>
    <div id="navigation-bar">
        <div class="content-width">
        </div>
    </div>
    <?php
        $bg_image = ($bot['backgroundImageDetails'] AND isset($bot['backgroundImageDetails']['originalUrl'])) ? $bot['backgroundImageDetails']['originalUrl'] : '';
        $has_bg_image = !!strlen($bg_image); 
        $bg_overlay_settings = ($bot['background_overlay_settings'] AND isset($bot['background_overlay_settings'])) ? $bot['background_overlay_settings'] : '';
        $has_bg_overlay_settings = !!($bg_overlay_settings); 
    ?>
    <div id="top-background" style="{{ $has_bg_image ? 'background-image: url('.$bg_image.');' : '' }}">
        <div style="background: {{ $has_bg_overlay_settings ? 'linear-gradient(90deg, '.$bg_overlay_settings['start'].', '.$bg_overlay_settings['end'].')' : 'none' }};"></div>
    </div>
    <div id="container" class="content-width">
        <!-- HEAD SECTION -->
        <div id="details">
            <div id="details-avatar">
                @if ($bot['hash'])
                <a href="{{ $bot->getPublicBotURL() }}" title="Return to the bot home page"><img src="{{ $bot->getRobohashURL() }}" class="center"></a>
                @else
                <span data-no-image></span>
                @endif
            </div>
            <div id="details-content">
                <h1><a href="{{ $bot->getPublicBotURL() }}" title="Return to the bot home page">{{ $bot['name'] }}</a></h1>
                <div class="name">Status: </div>
                <div id="BotStatusComponent" class="value">
                    {{-- REACT --}}
                    @if ($bot->isActive())
                    <div class="status-dot bckg-green"></div>Active
                    @else
                    <div class="status-dot bckg-red"></div>Inactive
                    @endif
                    {{-- <button class="button-question"></button> --}}
                </div>
                <div class="name">Address: </div>
                <div class="value">
                    <div id="BotCopyableAddress">{{-- REACT --}}</div>
                    {{-- <button class="button-question"></button> --}}
                </div>
                <div class="clearfix"></div>
            </div>
            <?php $logo_image = ($bot['logoImageDetails'] AND isset($bot['logoImageDetails']['thumbUrl'])) ? $bot['logoImageDetails']['thumbUrl'] : ''; $has_logo_image = !!strlen($logo_image); ?>
            @if ($has_logo_image)
            <div id="details-logo">
                <span><img src="{{ $logo_image }}" class="center"></span>
            </div>
            @endif
        </div>
        <!-- CONTENT SECTION -->
        <div id="content" class="grid-container">
            <!-- ACTION BUTTONS BAR -->
            <div id="main-buttons-bar">
                <button id="begin-swap-button" class="btn-action bckg-green">BEGIN SWAP</button>
                <button id="heart-button" class="btn-action bckg-red btn-stick-left float-right"><i class="fa fa-heart-o"></i></button>
                <button id="recent-swaps-button" class="btn-action bckg-yellow btn-stick-right btn-stick-left float-right">RECENT SWAPS</button>
                <button id="active-swaps-button" class="btn-action bckg-blue btn-stick-right float-right">ACTIVE SWAPS</button>
            </div>
            <!-- DEFAULT CONTENT -->
    

            <div id="SwapPurchaseStepsComponent">{{-- REACT --}}</div>


            <div class="clearfix"></div>


            <div id="RecentAndActiveSwapsComponent">{{-- REACT --}}</div>
            <div class="clearfix"></div>
        </div>
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
<script src="/bower_components/numeral/min/numeral.min.js"></script>
<script src="/bower_components/director/build/director.min.js"></script>
<script src="/bower_components/moment/min/moment.min.js"></script>
<script src="/bower_components/eventEmitter/EventEmitter.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/zeroclipboard/2.2.0/ZeroClipboard.min.js"></script>

{{-- pusher --}}
<script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
<script src="{{$pusherUrl}}/public/client.js"></script>

{{-- app --}}
<script src="/js/bot/bot-combined.js"></script>
<script>
    BotApp.init({!! json_encode($bot->serializeForAPI('public'), JSON_HEX_APOS) !!}, {url: '{!! $quotebot['url'] !!}', apiToken: '{!! $quotebot['apiToken'] !!}'}, '{!! $quotebotPusherUrl !!}')
</script>


{{-- pockets data holders --}}
<div data-pockets-url class="pockets-url" style="display: none;"></div><div data-pockets-image class="pockets-image" style="display: none;"></div>

</body>

</html>
