<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swapbot | Admin</title>

    <link href="/css/app.css" rel="stylesheet">

    <!-- Fonts -->
    <link href='//fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>

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
    <script src="/bower_components/mithril/mithril.min.js"></script>

    <div class="container" style="margin-top:0px; margin-bottom: 24px;">
        <div class="row">
            <div class="col-md-12 col-lg-10 col-lg-offset-1">
                <div id="admin"></div>
            </div>
        </div>
    </div>

    <!-- deps -->
    <script src="/bower_components/cryptojslib/rollups/sha1.js"></script>
    <script src="/bower_components/cryptojslib/rollups/hmac-sha256.js"></script>
    <script src="/bower_components/cryptojslib/components/enc-base64-min.js"></script>
    <script src="/bower_components/moment/min/moment.min.js"></script>

    <!-- pusher -->
    <script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
    <script src="{{$pusherUrl}}/public/client.js"></script>

    <!-- app -->
    @foreach ($admin_scripts as $script)
        <script src="/js/admin/{{$script}}"></script>
    @endforeach
</body>
</html>
