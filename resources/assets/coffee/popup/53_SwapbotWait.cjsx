
SwapbotWait = React.createClass
    displayName: 'SwapbotWait'

    componentDidMount: ()->
        bot = this.props.bot
        botId = bot.id
        $.get "/api/v1/public/botevents/#{botId}", (data)=>
            # console.log "botevents",data
            if this.isMounted()
                for botEvent in data
                    if botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
                        this.handleMatchedBotEvent(botEvent)
                        break
                

            return

        # subscribe to pusher
        this.state.pusherClient = this.subscribeToPusher(bot)

        return

    componentWillUnmount: ()->
        swapbot.pusher.closePusherChanel(this.state.pusherClient) if this.state.pusherClient
        return


    subscribeToPusher: (bot)->
        swapbot.pusher.subscribeToPusherChanel "swapbot_events_#{bot.id}", (botEvent)=>
            if botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
                this.handleMatchedBotEvent(botEvent)
        return

    # ########################################################################
    # matched bot event
    
    handleMatchedBotEvent: (botEvent)->
        event = botEvent.event

        matchedTxInfo = botEventWatcher.txInfoFromBotEvent(botEvent)

        matchedTxs = this.state.matchedTxs
        matchedTxs[matchedTxInfo.swapId] = matchedTxInfo
        this.setState({matchedTxs: matchedTxs, anyMatchedTxs: true})

        return

    selectMatchedTx: (matchedTxInfo)->
        if matchedTxInfo.status == 'swap.sent'
            this.props.swapDetails.txInfo = matchedTxInfo
            this.props.router.setRoute('/complete')
        else
            this.setState({matchedTxInfo: matchedTxInfo})

    # ########################################################################


    getInitialState: ()->
        return {
            'botEvents'    : [],
            'pusherClient' : null,
            'matchedTxInfo': null,
            'matchedTxs'   : {},
            'anyMatchedTxs': false,
        }

    goBack: (e)->
        e.preventDefault();
        this.props.router.setRoute('/receive')
        return

    notMyTransactionClicked: (e)->
        e.preventDefault()
        return

    buildChooseSwapClicked: (txInfo)->
        return (e)=>
            e.preventDefault()
            this.selectMatchedTx(txInfo)
            return

    render: ()->
        bot = this.props.bot
        swapDetails = this.props.swapDetails
        # console.log "this.state.matchedTxInfo="+(this.state.matchedTxInfo?)

        <div id="swap-step-3" className="swap-step">
            <h2>Waiting for confirmations</h2>
            <div className="segment-control">
                <div className="line"></div><br/>
                <div className="dot"></div>
                <div className="dot"></div>
                <div className="dot selected"></div>
                <div className="dot"></div>
            </div>


            {
                if this.state.matchedTxInfo?
                    # found a match
                    <p>
                        Received <b>{this.state.matchedTxInfo.inQty} {this.state.matchedTxInfo.inAsset}</b> from <br/>
                        {this.state.matchedTxInfo.address}.<br/>
                        <a id="not-my-transaction" onClick={this.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                    </p>
                else
                    # no transaction matched yet
                    if this.state.anyMatchedTxs
                        <div>
                        <ul className="wide-list">
                        {
                            for swapId, txInfo of this.state.matchedTxs
                                <li>
                                    <a onClick={this.buildChooseSwapClicked(txInfo)} href="#choose">
                                        <div className="item-header" title="{txInfo.name}">Transaction Received</div>
                                        <div className="icon-next"></div>
                                        <div>
                                            <p>
                                                Received <b>{txInfo.inQty} {txInfo.inAsset}</b> from <br/>
                                                {txInfo.address}.<br/>
                                                This transaction has <b>{txInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.
                                            </p>
                                            <p className="msg">{txInfo.msg}</p>
                                        </div>
                                    </a>
                                </li>
                            
                        }
                        </ul>
                        <a id="go-back" onClick={this.goBack} href="#" className="shadow-link">Go Back</a>
                        </div>
                    else
                        # no matched txs
                        <p>
                            Waiting for {swapDetails.chosenToken.inAmount} {swapDetails.chosenToken.inAsset} to be sent to<br/>
                            {bot.address}<br/>
                            <a id="cancel" onClick={this.goBack} href="#" className="shadow-link">Go Back</a>
                        </p>
                
            }


            <div className="pulse-spinner center">
                <div className="rect1"></div>
                <div className="rect2"></div>
                <div className="rect3"></div>
                <div className="rect4"></div>
                <div className="rect5"></div>
            </div>

            {
                if this.state.matchedTxInfo?
                    <div>
                        <p>This transaction has <b>{this.state.matchedTxInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                        <p className="msg">{this.state.matchedTxInfo.msg}</p>
                    </div>
                else
                    <p>
                        Waiting for transaction.  This transaction will require {swapbot.botUtils.confirmationsProse(bot)}.
                    </p>
            }

        </div>


