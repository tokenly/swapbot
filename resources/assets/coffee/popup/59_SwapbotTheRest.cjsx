###
SwapbotTheRest = React.createClass
    displayName: 'SwapbotTheRest'

    getInitialState: ()->
        console.log "this.props",this.props
        return {
            bot: null
            loaded: false
            botId: null
        }

    componentDidMount: ()->

        containerEl = jQuery(this.getDOMNode()).parent()
        botId = containerEl.data('bot-id')
        this.setState({botId: botId})
        $.get "/api/v1/public/bot/#{botId}", (data)=>
            if this.isMounted()
                console.log "data",data
                this.setState({bot: data})
            return


        console.log "this.state.containerEl=",this.state.containerEl
        console.log " this.state.botId=", this.state.botId
        return

    render: ->
        <div className={"swapbot-container " + if this.props.showing? then '' else 'hidden'}>
            <div className="header">
                <div className="avatar">
                    <img src="http://robohash.org/siemano.png?set=set3" />
                </div>
                <div className="status-dot bckg-green"></div>
                <h1><a href="http://raburski.com/swapbot0" target="_blank">{this.state.bot?.name}</a></h1>
            </div>
            <div className="content">
                <div id="swap-step-1" className="swap-step">
                    <h2>Choose a token to receive</h2>
                    <div className="segment-control">
                        <div className="line"></div><br/>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                    </div>
                    <p className="description">Short description about LTBCOIN. Short description about LTBCOIN. Short description about LTBCOIN. <a className="more-link" href="#" target="_blank"><i className="fa fa-sign-out"></i></a></p>
                    <ul className="wide-list">
                        <li><a href="#move-to-step-2-for-BTC">
                            <div className="item-header">BTC <small>(7.78973 available)</small></div>
                            <p>Sends 1 BTC for 1,000,000 LTBCOIN or 1,000,000 NOTLTBCOIN.</p>
                            <div className="icon-next"></div>
                        </a></li>
                        <li><a href="#move-to-step-2-for-LTBCOIN">
                            <div className="item-header">LTBCOIN <small>(98778973 available)</small></div>
                            <p>Sends 1 LTBCOIN for each 0.000001 BTC or 1 NOTLTBCOIN.</p>
                            <div className="icon-next"></div>
                        </a></li>
                        <li><a href="#move-to-step-2-for-NOTLTBCOIN">
                            <div className="item-header">NOTLTBCOIN <small>(0 available)</small></div>
                            <p>Sends 1 NOTLTBCOIN for each 1 LTBCOIN or 0.000001 BTC.</p>
                            <div className="icon-denied"></div>
                        </a></li>
                    </ul>
                </div>

                <div id="swap-step-2" className="swap-step hidden">
                    <h2>Receiving transaction</h2>
                    <div className="segment-control">
                        <div className="line"></div><br/>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                    </div>
                    <table className="fieldset">
                        <tr><td><label htmlFor="token-available">LTBCOIN available for purchase: </label></td>
                        <td><span id="token-available">100,202,020 LTBCOIN</span></td></tr>

                        <tr><td><label htmlFor="token-amount">I would like to purchase: </label></td>
                        <td><input type="text" id="token-amount" placeholder="0 LTBCOIN"></td></tr>
                    </table>
                    <ul className="wide-list">
                        <li>
                            <div className="item-header">Send <span id="token-value-1">0</span> BTC to</div>
                            <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys?amount=0.1">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                            <a href="#open-wallet-url"><div className="icon-wallet"></div></a>
                            <div className="icon-qr"></div>

                            <img className="qr-code-image hidden" src="/images/avatars/qrcode.png">
                            <div className="clearfix"></div>
                        </li>
                        <li>
                            <div className="item-header">Send <span id="token-value-2">0</span> NOTLTBCOIN to</div>
                            <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                            <a href="#open-wallet-url"><div className="icon-wallet"></div></a>
                            <div className="icon-qr"></div>

                            <img className="qr-code-image hidden" src="/images/avatars/qrcode.png">
                            <div className="clearfix"></div>
                        </li>
                    </ul>

                    <p className="description">After receiving one of those token types, this bot will wait for <b>2 confirmations</b> and return tokens <b>to the same address</b>.</p>
                </div>

                <div id="swap-step-3-other" className="swap-step hidden">
                    <h2>Provide source address</h2>
                    <div className="segment-control">
                        <div className="line"></div><br/>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                    </div>

                    <p className="description">Please provide us address you have sent your funds from so we can find your transaction. (or some other warning)</p>
                    <table className="fieldset fieldset-other">
                        <tr><td><input type="text" id="other-address" placeholder="1xxxxxxx..."></td><td><div style={{float:"left"}} id="icon-other-next" className="icon-next"></div></td></tr>
                    </table>
                </div>

                <div id="swap-step-3" className="swap-step hidden">
                    <h2>Waiting for confirmations</h2>
                    <div className="segment-control">
                        <div className="line"></div><br/>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                    </div>

                    <p>
                        Received <b>0.1 BTC</b> from <br/>1MySUperHyPerAddreSSNoTOTak991s.<br/>
                        <a id="not-my-transaction" href="#" className="shadow-link">Not your transaction?</a>
                    </p>
                    <div className="pulse-spinner center">
                        <div className="rect1"></div>
                        <div className="rect2"></div>
                        <div className="rect3"></div>
                        <div className="rect4"></div>
                        <div className="rect5"></div>
                    </div>

                    <p>Transaction has <b>0 out of 2</b> required confirmations.</p>
                </div>

                <div id="swap-step-4" className="swap-step hidden">
                    <h2>Successfully finished</h2>
                    <div className="segment-control">
                        <div className="line"></div><br/>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                    </div>

                    <div className="icon-success center"></div>

                    <p>Exchanged <b>0.1 BTC</b> for <b>100,000 LTBCOIN</b> with 1MySUperHyPerAddreSSNoTOTak991s.</p>
                    <p><a href="#" className="details-link" target="_blank">Transaction details <i className="fa fa-arrow-circle-right"></i></a></p>
                </div>
                <div className="footer">powered by <a href="http://swapbot.co/" target="_blank">Swapbot</a></div>
            </div>
        </div>
###