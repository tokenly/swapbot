<!doctype html>

<head>
    <meta charset="utf-8">
    <title>Swapbot</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">
    <link href="/css/main.css" rel="stylesheet">
</head>

<body>
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
                <img src="{{ $bot->getRobohashURL() }}" class="center">
                @else
                <span data-no-image></span>
                @endif
            </div>
            <div id="details-content">
                <h1>{{ $bot['name'] }}</h1>
                <div class="name">Status: </div>
                <div id="BotStatusComponent" class="value">
                    {{-- REACT --}}
                    @if ($bot->isActive())
                    <div class="status-dot bckg-green"></div>Active
                    @else
                    <div class="status-dot bckg-red"></div>Inactive
                    @endif
                    <button class="button-question"></button>
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
                <button id="begin-swap-button" class="btn-action bckg-green">BEGIN SWAP</button>
                <button id="heart-button" class="btn-action bckg-red btn-stick-left float-right"><i class="fa fa-heart-o"></i></button>
                <button id="recent-swaps-button" class="btn-action bckg-yellow btn-stick-right btn-stick-left float-right">RECENT SWAPS</button>
                <button id="active-swaps-button" class="btn-action bckg-blue btn-stick-right float-right">ACTIVE SWAPS</button>
            </div>
            <!-- DEFAULT CONTENT -->
    

            <div id="SwapInterfaceComponent">{{-- REACT --}}</div>


{{-- 
            <div id="swapbot-container" class="section grid-100 hidden">
                <div id="swap-step-2" class="content hidden">
                    <h2>Receiving transaction</h2>
                    <div class="segment-control">
                        <div class="line"></div>
                        <br>
                        <div class="dot"></div>
                        <div class="dot selected"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                    <table class="fieldset">
                        <tr>
                            <td>
                                <label for="token-available">LTBCOIN available for purchase: </label>
                            </td>
                            <td><span id="token-available">100,202,020 LTBCOIN</span></td>
                        </tr>
                        <tr>
                            <td>
                                <label for="token-amount">I would like to purchase: </label>
                            </td>
                            <td>
                                <input type="text" id="token-amount" placeholder="0 LTBCOIN">
                            </td>
                        </tr>
                    </table>
                    <ul id="transaction-select-list" class="wide-list">
                        <li>
                            <div class="item-header">Send <span id="token-value-1">0</span> BTC to</div>
                            <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys?amount=0.1">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                            <a href="#open-wallet-url">
                                <div class="icon-wallet"></div>
                            </a>
                            <div class="icon-qr"></div>
                            <img class="qr-code-image hidden" src="/images/avatars/qrcode.png">
                            <div class="clearfix"></div>
                        </li>
                        <li>
                            <div class="item-header">Send <span id="token-value-2">0</span> NOTLTBCOIN to</div>
                            <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                            <a href="#open-wallet-url">
                                <div class="icon-wallet"></div>
                            </a>
                            <div class="icon-qr"></div>
                            <img class="qr-code-image hidden" src="/images/avatars/qrcode.png">
                            <div class="clearfix"></div>
                        </li>
                    </ul>
                    <ul id="transaction-wait-list" class="wide-list hidden">
                        <li>
                            <div class="status-icon icon-pending"></div> Waiting for <b>0.12 BTC</b> sent to <a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a>.
                            <br><small>Side DEMO note: when transaction is smart-guessed list will be skipped.</small>
                        </li>
                    </ul>
                    <ul id="transaction-confirm-list" class="wide-list hidden">
                        <li>
                            <div class="item-content">
                                <div class="item-header">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</div>
                                <p>
                                    Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br>
                                </p>
                            </div>
                            <div class="item-actions">
                                <div class="icon-next"></div>
                            </div>
                            <div class="clearfix"></div>
                        </li>
                        <li>
                            <div class="item-content">
                                <div class="item-header">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyy2</div>
                                <p>
                                    Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please.
                                    <br> Any data and as long as you please. YES.
                                    <br>
                                </p>
                            </div>
                            <div class="item-actions">
                                <div class="icon-next"></div>
                            </div>
                            <div class="clearfix"></div>
                        </li>
                    </ul>
                    <p class="description">After receiving one of those token types, this bot will wait for <b>2 confirmations</b> and return tokens <b>to the same address</b>.</p>
                </div>
                <div id="swap-step-3-other" class="content hidden">
                    <h2>Provide source address</h2>
                    <div class="segment-control">
                        <div class="line"></div>
                        <br>
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot selected"></div>
                        <div class="dot"></div>
                    </div>
                    <p class="description">Please provide us address you have sent your funds from so we can find your transaction. (or some other warning)</p>
                    <table class="fieldset fieldset-other">
                        <tr>
                            <td>
                                <input type="text" id="other-address" placeholder="1xxxxxxx...">
                            </td>
                            <td>
                                <div style="float:left" id="icon-other-next" class="icon-next"></div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="swap-step-3" class="content hidden">
                    <h2>Waiting for confirmations</h2>
                    <div class="segment-control">
                        <div class="line"></div>
                        <br>
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot selected"></div>
                        <div class="dot"></div>
                    </div>
                    <div class="icon-loading center"></div>
                    <p>
                        Received <b>0.1 BTC</b> from
                        <br>1MySUperHyPerAddreSSNoTOTak991s.
                        <br>
                        <a id="not-my-transaction" href="#" class="shadow-link">Not your transaction?</a>
                    </p>
                    <p>Transaction has <b>0 out of 2</b> required confirmations.</p>
                    <p>
                        <br>Don't want to wait here?
                        <br>We can notify you when the transaction is done!
                        <table class="fieldset fieldset-other">
                            <tr>
                                <td>
                                    <input type="text" id="other-address" placeholder="example@example.com">
                                </td>
                                <td>
                                    <div id="icon-other-next" class="icon-next"></div>
                                </td>
                            </tr>
                        </table>
                    </p>
                </div>
                <div id="swap-step-4" class="content hidden">
                    <h2>Successfully finished</h2>
                    <div class="x-button" id="swap-step-4-close"></div>
                    <div class="segment-control">
                        <div class="line"></div>
                        <br>
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot selected"></div>
                    </div>
                    <div class="icon-success center"></div>
                    <p>Exchanged <b>0.1 BTC</b> for <b>100,000 LTBCOIN</b> with 1MySUperHyPerAddreSSNoTOTak991s.</p>
                    <p><a href="details.html" class="details-link" target="_blank">Transaction details <i class="fa fa-arrow-circle-right"></i></a></p>
                </div>
            </div>
--}}


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
<script src="http://fb.me/react-0.13.2.js"></script>
<script src="/bower_components/director/build/director.min.js"></script>
<script src="/bower_components/moment/min/moment.min.js"></script>

{{-- pusher --}}
<script>window.PUSHER_URL = '{{$pusherUrl}}';</script>
<script src="{{$pusherUrl}}/public/client.js"></script>

{{-- app --}}
<script src="/js/bot/bot-combined.js"></script>
<script>BotApp.init({!! json_encode($bot->serializeForAPI('public'), JSON_HEX_APOS) !!})</script>



</body>

</html>
