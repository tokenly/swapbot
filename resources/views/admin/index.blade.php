<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swapbot | Admin</title>

    <link href="/css/admin.css" rel="stylesheet">

    <!-- Fonts -->
    <link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

    @if ($bugsnag['apiKey'])
    <script src="//d2wy8f7a9ursnm.cloudfront.net/bugsnag-2.min.js" data-apikey="{{$bugsnag['apiKey']}}"></script>
    <script>Bugsnag.releaseStage = "{{$bugsnag['releaseStage']}}";</script>
    @endif

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <link rel="icon" href="/images/icons/sb-favicon.png">

</head>
<body>


    <!-- Scripts -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script src="/bower_components/mithril/mithril.min.js"></script>


    <div id="admin"></div>

    {{-- pockets data holders --}}
    <div data-pockets-url class="pockets-url" style="display: none;"></div><div data-pockets-image class="pockets-image" style="display: none;"></div>

    <!-- deps -->
    <script src="/bower_components/cryptojslib/rollups/sha1.js"></script>
    <script src="/bower_components/cryptojslib/rollups/sha256.js"></script>
    <script src="/bower_components/cryptojslib/rollups/hmac-sha256.js"></script>
    <script src="/bower_components/cryptojslib/components/enc-base64-min.js"></script>
    <script src="/bower_components/moment/min/moment.min.js"></script>
    {{-- <script src="/bower_components/accounting.js/accounting.min.js"></script> --}}
    <script src="/bower_components/numeral/min/numeral.min.js"></script>
    <!-- pusher -->
    <script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
    <script src="{{$pusherUrl}}/public/client.js"></script>

    <!-- app -->
    <script>
        window.QUOTEBOT_PUSHER_URL = '{{$quotebotPusherUrl}}';
        window.QUOTEBOT_URL = '{{$quotebot['url']}}';
        window.QUOTEBOT_API_TOKEN = '{{$quotebot['apiToken']}}';
    </script>
    @foreach ($admin_scripts as $script)
        <script src="/js/admin/{{$script}}"></script>
    @endforeach
</body>
</html>
