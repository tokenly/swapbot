<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swapbot</title>

    <link href="/css/bot.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>


    <!-- Scripts -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js"></script>
    {{--
    <script src="/bower_components/mithril/mithril.min.js"></script>
    --}}

    <div id="navigation-bar">
        <div class="content-width">
        </div>
    </div>
    <div id="top-background">
        <div></div>
    </div>
    <div id="container" class="content-width">
        <!-- HEAD SECTION -->
        <div id="details">
            <div id="details-avatar">
                @if ($bot['hash'])
                <img src="http://robohash.org/{{ $bot['hash'] }}.png?set=set3" class="center">
                @else
                <span data-no-image></span>
                @endif
            </div>
            <div id="details-content">
                <h1>{{ $bot['name'] }}</h1>
                <div class="name">Status: </div>
                <div class="value">
                    @if ($bot->isActive())
                    <div class="status-dot bckg-green"></div>Active
                    @else
                    <div class="status-dot bckg-red"></div>Inactive
                    @endif
                    <button class="button-question" title="Info"></button>
                </div>
                <div class="name">Address: </div>
                <div class="value">
                    <a class="swap-address" href="bitcoin:{{ $bot['address'] }}">{{ $bot['address'] }} </a>
                    <button class="button-question"></button>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- CONTENT SECTION -->
        <div id="content" class="grid-container">
            <!-- ACTION BUTTONS BAR -->
            <div id="main-buttons-bar">
                <button id="begin-swap-button" class="btn-action bckg-green" onclick="javascript:window.open('{{ $bot['uuid'] }}/popup')">BEGIN SWAP</button>
                <button id="heart-button" class="btn-action bckg-red btn-stick-left float-right"><i class="fa fa-heart-o"></i></button>
                <button id="recent-swaps-button" class="btn-action bckg-yellow btn-stick-right btn-stick-left float-right">RECENT SWAPS</button>
                <button id="active-swaps-button" class="btn-action bckg-blue btn-stick-right float-right">ACTIVE SWAPS</button>
            </div>
            <!-- DEFAULT CONTENT -->
            <div id="swap-step-1">
                <div class="section grid-50">
                    <h3>Description</h3>
                    <div class="description">{{ $bot['description'] }}</div>
                </div>
                <div class="section grid-50">
                    <h3>Available Swaps</h3>
                    <div id="SwapsList">{{-- REACT --}}</div>
                </div>
            </div>
            <div class="clearfix"></div>


            <div id="SwapStatuses"></div>

            <div class="clearfix">{{-- REACT --}}</div>
        </div>
    </div>


    <footer>
        Swapbot is a Tokenly service.
    </footer>

</body>

{{-- Scripts --}}
<script src="/js/public/asyncLoad.js"></script>
<script>
    window.asyncLoad("//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", "css");
    window.asyncLoad("//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700", "css");
</script>
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>

<script src="http://fb.me/react-0.13.1.js"></script>
{{-- <script src="/bower_components/director/build/director.min.js"></script> --}}
{{-- <script src="/bower_components/moment/min/moment.min.js"></script> --}}

<!-- pusher -->
<script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
<script src="{{$pusherUrl}}/public/client.js"></script>

{{-- app --}}
<script src="/js/bot/bot-combined.js"></script>
<script>BotApp.init({!! json_encode($bot->serializeForAPI('public'), JSON_HEX_APOS) !!})</script>

</html>
