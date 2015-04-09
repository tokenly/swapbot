
TransactionInfo = React.createClass
    displayName: 'TransactionInfo'
    intervalTimer: null

    componentDidMount: ()->
        this.updateNow()

        this.intervalTimer = setInterval ()=>
            this.updateNow()
        , 1000

        return

    updateNow: ()->
        this.setState({fromNow: moment(this.props.txInfo.createdAt).fromNow()})
        # this.setState({fromNow: moment().format('MMMM Do YYYY, h:mm:ss a')})
        return

    componentWillUnmount: ()->
        if this.intervalTimer?
            clearInterval(this.intervalTimer)
        return

    getInitialState: ()->
        return {
            fromNow: ''
        }

    render: ()->
        txInfo = this.props.txInfo
        bot = this.props.bot

        return <li>
            <a onClick={this.props.clickedFn} href="#choose">
                <div className="item-header" title="{txInfo.name}">Transaction Received</div>
                <div className="icon-next"></div>
                <div>
                    <p className="date">{ this.state.fromNow }</p>
                    <p>
                        Received <b>{txInfo.inQty} {txInfo.inAsset}</b> from <br/>
                        {txInfo.address}.<br/>
                        This transaction has <b>{txInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.
                    </p>
                    <p className="msg">{txInfo.msg}</p>
                </div>
            </a>
        </li>





SwapbotWait = React.createClass
    displayName: 'SwapbotWait'
    intervalTimer: null

    componentDidMount: ()->
        bot = this.props.bot
        botId = bot.id
        $.get "/api/v1/public/botevents/#{botId}", (data)=>
            # console.log "botevents",data
            if this.isMounted()
                for botEvent in data
                    if botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
                        this.handleMatchedBotEvent(botEvent)
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
        matchedTxInfo = botEventWatcher.txInfoFromBotEvent(botEvent)
        swapId = matchedTxInfo.swapId

        matchedTxs = this.state.matchedTxs
        
        if matchedTxs[swapId]?
            if matchedTxs[swapId].serial >= botEvent.serial
                # this event is older than the one we have - ignore it
                return

        # apply to the selected matchedTxInfo
        #   if it exists
        selectedMatchedTxInfo = this.state.selectedMatchedTxInfo
        if selectedMatchedTxInfo? and selectedMatchedTxInfo.swapId == swapId
            # update the selectedMatchedTxInfo
            selectedMatchedTxInfo = matchedTxInfo

        # also update the list of matchedTxs
        matchedTxs[swapId] = matchedTxInfo
        this.setState({
            selectedMatchedTxInfo: selectedMatchedTxInfo
            matchedTxs           : matchedTxs
            anyMatchedTxs        : true
        })

        return

    selectMatchedTx: (matchedTxInfo)->
        if matchedTxInfo.status == 'swap.sent'
            this.props.swapDetails.txInfo = matchedTxInfo
            this.props.router.setRoute('/complete')
        else
            this.setState({selectedMatchedTxInfo: matchedTxInfo})

    # ########################################################################


    getInitialState: ()->
        return {
            botEvents            : [],
            pusherClient         : null,
            selectedMatchedTxInfo: null,
            matchedTxs           : {},
            anyMatchedTxs        : false,
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
        # console.log "this.state.selectedMatchedTxInfo="+(this.state.selectedMatchedTxInfo?)

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
                if this.state.selectedMatchedTxInfo?
                    # found a match
                    <p>
                        Received <b>{this.state.selectedMatchedTxInfo.inQty} {this.state.selectedMatchedTxInfo.inAsset}</b> from <br/>
                        {this.state.selectedMatchedTxInfo.address}.<br/>
                        <a id="not-my-transaction" onClick={this.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                    </p>
                else
                    # no transaction matched yet
                    if this.state.anyMatchedTxs
                        <div>
                            <ul className="wide-list">
                            {
                                for swapId, txInfo of this.state.matchedTxs
                                    <TransactionInfo bot={bot} txInfo={txInfo} clickedFn={this.buildChooseSwapClicked(txInfo)} />
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
                if this.state.selectedMatchedTxInfo?
                    <div>
                        <p>This transaction has <b>{this.state.selectedMatchedTxInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                        <p className="msg">{this.state.selectedMatchedTxInfo.msg}</p>
                    </div>
                else
                    <p>
                        Waiting for transaction.  This transaction will require {swapbot.botUtils.confirmationsProse(bot)}.
                    </p>
            }

        </div>


