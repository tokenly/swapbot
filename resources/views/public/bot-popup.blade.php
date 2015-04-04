<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swapbot | {{ $bot['name'] }}</title>

    <link href="/css/popup.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

    <div id="SwapbotPopup" data-bot-id="{{ $bot['id'] }}"></div>

    

</body>


{{-- Scripts --}}
<script src="/js/public/asyncLoad.js"></script>
<script>
    window.asyncLoad("//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", "css");
    window.asyncLoad("//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700", "css");
</script>
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
<script src="http://fb.me/react-0.13.1.js"></script>
<script src="/bower_components/director/build/director.min.js"></script>

<!-- pusher -->
<script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
<script src="{{$pusherUrl}}/public/client.js"></script>

<script src="/js/popup/popup-combined.js"></script>
<script>SwapbotApp.init()</script>
</html>
