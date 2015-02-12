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
    {{--
    <script src="/bower_components/mithril/mithril.min.js"></script>
    --}}


    <div id="bot">
        <nav class=" navbar navbar-default">
            <div class=" container-fluid">
                <div class=" navbar-header"><a href="/" class=" navbar-brand">Swapbot</a>
                </div>
                <ul class=" nav navbar-nav">
                    <li><a href="/">Home</a>
                    </li>
                </ul>
            </div>
        </nav>
        <div class=" container" style="margin-top: 0px; margin-bottom: 24px;">
            <div class=" row">
                <div class=" col-md-12 col-lg-10 col-lg-offset-1">
                    <div>
                        <h2>The Sample LTBCOIN Bot</h2>
                        <div class=" spacer1"></div>
                        <div class=" row">
                            <div class=" col-md-12">
                                <button class="btn btn-success" data-toggle="modal" data-target="#myModal">Begin Swap</button>
                                <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                                                </button>
                                                <h4 class="modal-title" id="myModalLabel">Send Tokens to 1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form class="form-horizontal">
                                                    <div class="form-group">
                                                        <label for="address" class="col-sm-4 control-label">Enter Your Address</label>
                                                        <div class="col-sm-8">
                                                            <input type="email" class="form-control" id="address" value="1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD" placeholder="1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                                        </div>
                                                    </div>
                                                </form>

                                                <p>Send tokens to 1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys from address 1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD in your Counterparty wallet.</p>
                                                <div class="spacer1"></div>
                                                <div class=" pulse-spinner pull-right">
                                                    <div class=" rect1"></div>
                                                    <div class=" rect2"></div>
                                                    <div class=" rect3"></div>
                                                    <div class=" rect4"></div>
                                                    <div class=" rect5"></div>
                                                </div>
                                                <h5>Watching for Transactions from 1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD</h5>
                                                <div class="spacer1"></div>

                                                <div class="transaction panel panel-primary">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title">Transaction received</h3>
                                                    </div>
                                                    <div class="panel-body">Received 100,000 LTBCOIN from 1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD with 0 confirmations.  Preparing to send 0.1 BTC</div>
                                                    <div class="panel-footer"><small>Waiting for 1 confirmation</small></div>
                                                </div>

                                                <p>You can close this popup or your browser at any time and check back later.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class=" spacer1"></div>
                        <div class=" bot-view">
                            <div class=" row">
                                <div class=" col-md-3">
                                    <div class=" form-group">
                                        <label for="name" class=" control-label">Bot Name</label>
                                        <div id="name" class=" form-control-static">The Sample LTBCOIN Bot</div>
                                    </div>
                                </div>
                                <div class=" col-md-4">
                                    <div class=" form-group">
                                        <label for="address" class=" control-label">Address</label>
                                        <div id="address" class=" form-control-static">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</div>
                                    </div>
                                </div>
                                <div class=" col-md-5">
                                    <div class=" form-group">
                                        <label for="status" class=" control-label">Status</label>
                                        <div id="status" class=" form-control-static">
                                            <p><span style="color: green; font-weight: bold;">Active</span><br/> This bot is receiving and sending transactions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class=" row">
                                <div class=" col-md-12">
                                    <div class=" form-group">
                                        <label for="description" class=" control-label">Bot Description</label>
                                        <div id="description" class=" form-control-static">This Swapbot trades BTC against LTBCOIN.</div>
                                    </div>
                                </div>
                            </div>
                            <h4>Swaps Available</h4>
                                <div class="list-group">
                                  <div class="list-group-item">
                                    <h5 class="list-group-item-heading">Swap #1</h5>
                                    <p class="list-group-item-text">Receives BTC and sends 1,000,000 LTBCOIN for each 1 BTC received.</p>
                                  </div>
                                  <div class="list-group-item">
                                    <h5 class="list-group-item-heading">Swap #2</h5>
                                    <p class="list-group-item-text">Receives LTBCOIN and sends 0.000001 BTC for each 1 LTBCOIN received.</p>
                                  </div>
                                </div>

                            <div class="spacer2"></div>

                            <div class=" bot-events">
                                <div class=" pulse-spinner pull-right">
                                    <div class=" rect1"></div>
                                    <div class=" rect2"></div>
                                    <div class=" rect3"></div>
                                    <div class=" rect4"></div>
                                    <div class=" rect5"></div>
                                </div>
                                <h3>Swaps</h3>
                                <form class="form-horizontal">
                                    <div class="form-group">
                                        <label for="address" class="col-md-offset-6 col-md-2 col-sm-3 control-label">Search By Address</label>
                                        <div class="col-sm-9 col-md-4">
                                            <input type="email" class="form-control" id="address" placeholder="1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                        </div>
                                    </div>
                                </form>

                                <h4>Active Swaps</h4>

                                <div class="transaction panel panel-primary">
                                    <div class="panel-heading">
                                        <div class="pull-right">2 minutes ago</div>
                                        <h3 class="panel-title">Transaction received from 1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD</h3>
                                    </div>
                                    <div class="panel-body">Received 100,000 LTBCOIN from 1MyPersOnAlAddr3ss8EASgiVBaCe6f7cD with 0 confirmations.  Preparing to send 0.1 BTC</div>
                                    <div class="panel-footer"><small>Waiting for 1 confirmation</small></div>
                                </div>

                                <div class="spacer2"></div>

                                <h4>Recent Swaps</h4>

                                <div class="transaction panel panel-success">
                                    <div class="panel-heading">
                                        <div class="pull-right">20 minutes ago</div>
                                        <h3 class="panel-title">Transaction completed to 13xaXJmrA31e3Nqase3Dvo9NJS2eZUCHLs</h3>
                                    </div>
                                    <div class="panel-body">Received 100,000 LTBCOIN and sent 0.1 BTC to 13xaXJmrA31e3Nqase3Dvo9NJS2eZUCHLs.</div>
                                    <div class="panel-footer"><small>The transaction id is de3b6d181cb8363f4de8364a431e8f44550445f50b64b9a6562b189618897902</small></div>
                                </div>

                                <div class="transaction panel panel-success">
                                    <div class="panel-heading">
                                        <div class="pull-right">1 hour ago</div>
                                        <h3 class="panel-title">Transaction completed to 13xaXJmrA31e3Nqase3Dvo9NJS2eZUCHLs</h3>
                                    </div>
                                    <div class="panel-body">Received 0.2 BTC and sent 200,000 LTBCOIN to 1MQwfiZzKy9kUHVzCMVFuXaYtH3w2GsHcK.</div>
                                    <div class="panel-footer"><small>The transaction id is de3b6d181cb8363f4de8364a431e8f44550445f50b64b9a6562b189618897902</small></div>
                                </div>

                                <div class="transaction panel panel-danger">
                                    <div class="panel-heading">
                                        <div class="pull-right">Feb. 10, 8:25 PM GMT -06:00</div>
                                        <h3 class="panel-title">Transaction failed to 1XTC75eSKZuMTHeV5fw2TLPNzzP1pZZd9</h3>
                                    </div>
                                    <div class="panel-body">Received 100,000 FOOCOIN from 1XTC75eSKZuMTHeV5fw2TLPNzzP1pZZd9 which this bot does not accept.  Please contact team@tokenly.co for assistance.</div>
                                    <div class="panel-footer"><small>The transaction id is 4d536dd43fb492b028012c8fdc3de0a9b08f63263cd08436597b36e5638ab084</small></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        Swapbot is a Tokenly service.
    </footer>

</body>

</html>
