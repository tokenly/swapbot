<!doctype html>

<head>
    <meta charset="utf-8">
    <title>Swapbot</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link href="/css/utility.css" rel="stylesheet">
</head>

<body>

{{--     <div id="navigation-bar">
        <div class="content-width">
        </div>
    </div>
 --}}

    <div id="top-background">
        <div></div>
    </div>

    <div id="container" class="content-width">
        <!-- HEAD SECTION -->
        <div id="header">
            <div id="header-content">
                @yield('header_content')
            </div>
        </div>
    </div>

    <!-- CONTENT SECTION -->
    <div id="content" class="content-width">
        @yield('content')
        <div class="clearfix"></div>
    </div>

{{-- Scripts --}}
<script src="/js/public/asyncLoad.js"></script>
<script>
    window.asyncLoad("//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", "css");
    window.asyncLoad("//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700", "css");
</script>

</body>
</html>
