
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
            <div className="item-content">
                <a onClick={this.props.clickedFn} href="#choose">
                    <div className="item-header" title="{txInfo.name}">Transaction Received</div>
                    <p className="date">{ this.state.fromNow }</p>
                    <p>
                        Received <b>{txInfo.inQty} {txInfo.inAsset}</b> from {txInfo.address}.
                    </p>
                    <p>{txInfo.msg}</p>
                    <p>This transaction has <b>{txInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                </a>
            </div>
            <div className="item-actions">
                <a onClick={this.props.clickedFn} href="#choose"><div className="icon-next"></div></a>
            </div>
            <div className="clearfix"></div>
        </li>

                            # <li>
                            #     <div className="item-content">
                            #         <div className="item-header">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</div>
                            #         <p>
                            #             Any data and as long as you please.
                            #             <br/> Any data and as long as you please.
                            #             <br/> Any data and as long as you please.
                            #             <br/> Any data and as long as you please.
                            #             <br/> Any data and as long as you please.
                            #             <br/>
                            #         </p>
                            #     </div>
                            #     <div className="item-actions">
                            #         <div className="icon-next"></div>
                            #     </div>
                            #     <div className="clearfix"></div>
                            # </li>


# ########################################################################################################################


SingleTransactionInfo = React.createClass
    displayName: 'SingleTransactionInfo'
    intervalTimer: null

    componentDidMount: ()->
        this.updateNow()

        this.intervalTimer = setInterval ()=>
            this.updateNow()
        , 1000

        return

    updateNow: ()->
        this.setState({fromNow: moment(this.props.txInfo.createdAt).fromNow()})
        return

    componentWillUnmount: ()->
        if this.intervalTimer?
            clearInterval(this.intervalTimer)
        return

    getInitialState: ()->
        return {
            fromNow: ''
            emailValue: ''
            submittingEmail: false
            submittedEmail: false
        }

    updateEmailValue: (e)->
        e.preventDefault()
        this.setState({emailValue: e.target.value});
        return




    submitEmailFn: (e)->
        e.preventDefault()
        return if this.state.submittingEmail

        email = this.state.emailValue
        console.log "submitting email: #{email}"
        this.setState({submittingEmail: true})
        setTimeout ()=>
            this.setState({submittedEmail: true, submittingEmail: false})
        , 750
        return

    render: ()->
        txInfo = this.props.txInfo
        bot = this.props.bot
        emailValue = this.state.emailValue

        return <div id="swap-step-3" className="content">
                <h2>Waiting for confirmations</h2>
                <div className="segment-control">
                    <div className="line"></div>
                    <br/>
                    <div className="dot"></div>
                    <div className="dot"></div>
                    <div className="dot selected"></div>
                    <div className="dot"></div>
                </div>
                <div className="icon-loading center"></div>
                <p>
                    Received <b>{txInfo.inQty} {txInfo.inAsset}</b> from {txInfo.address}.
                    <br/>
                    <a id="not-my-transaction" onClick={this.props.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                </p>
                <p>This transaction has <b>{txInfo.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                {
                    if this.state.submittedEmail
                        <p>
                            <strong>Email address submitted.</strong>  Please check your email.
                        </p>
                    else
                        <p>
                            Don{"'"}t want to wait here?
                            <br/>We can notify you when the transaction is done!
                        </p>
                        <form action="#submit-email" onSubmit={this.submitEmailFn} style={if this.state.submittingEmail then {opacity: 0.2} else null}>
                            <table className="fieldset fieldset-other">
                                <tbody>
                                    <tr>
                                        <td>
                                            <input disabled={if this.state.submittingEmail then true else false} type="email" onChange={this.updateEmailValue} id="other-address" placeholder="example@example.com" value={emailValue} />
                                        </td>
                                        <td>
                                            <div id="icon-other-next" className="icon-next" onClick={this.submitEmailFn}></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                }
            </div>


# ########################################################################################################################


SwapbotWait = React.createClass
    displayName: 'SwapbotWait'
    subscriberId: null

    componentDidMount: ()->
        this.subscriberId = this.props.eventSubscriber.subscribe (botEvent)=>
            if this.isMounted()
                console.log "botEvent.event.name=#{botEvent.event.name} matches (#{this.props.swapDetails.chosenToken.inAmount}, #{this.props.swapDetails.chosenToken.inAsset})=",botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
                if botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
                    this.handleMatchedBotEvent(botEvent)

        return

    componentWillUnmount: ()->
        if this.subscriberId?
            this.props.eventSubscriber.unsubscribe(this.subscriberId)
            this.subscriberId = null
        return


    # subscribeToPusher: (bot)->
    #     swapbot.pusher.subscribeToPusherChanel "swapbot_events_#{bot.id}", (botEvent)=>
    #         if botEventWatcher.botEventMatchesInAmount(botEvent, this.props.swapDetails.chosenToken.inAmount, this.props.swapDetails.chosenToken.inAsset)
    #             this.handleMatchedBotEvent(botEvent)
    #     return

    # ########################################################################
    # matched bot event
    
    handleMatchedBotEvent: (botEvent)->
        console.log "handleMatchedBotEvent"
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
        console.log "matchedTxInfo",matchedTxInfo
        if matchedTxInfo.status == 'swap.sent'
            this.props.swapDetails.txInfo = matchedTxInfo
            this.props.router.setRoute('/complete')
        else
            this.setState({selectedMatchedTxInfo: matchedTxInfo})

    # ########################################################################


    getInitialState: ()->
        return {
            botEvents            : [],
            selectedMatchedTxInfo: null,
            matchedTxs           : {},
            anyMatchedTxs        : false,
        }

    goBack: (e)->
        e.preventDefault();
        this.props.router.setRoute('/receive')
        return

    notMyTransactionClicked: (e)->
        this.setState({
            selectedMatchedTxInfo: null
        })

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
        swap = swapDetails.swap
        # console.log "this.state.selectedMatchedTxInfo="+(this.state.selectedMatchedTxInfo?)


        return <div id="swapbot-container" className="section grid-100">
            <div id="swap-step-2" className="content">
                <h2>Receiving transaction</h2>
                <div className="segment-control">
                    <div className="line"></div>
                    <br />
                    <div className="dot"></div>
                    <div className="dot selected"></div>
                    <div className="dot"></div>
                    <div className="dot"></div>
                </div>
                <table className="fieldset">
                    <tr>
                        <td>
                            <label htmlFor="token-available">{swap.out} available for purchase: </label>
                        </td>
                        <td><span id="token-available">{bot.balances[swap.out]} {swap.out}</span></td>
                    </tr>
                    <tr>
                        <td>
                            <label htmlFor="token-amount">I would like to purchase: </label>
                        </td>
                        <td>
                            <input disabled type="text" id="token-amount" placeholder={'0 '+swap.out} defaultValue={this.props.swapDetails.chosenToken.outAmount} />
                        </td>
                    </tr>
                </table>


                {
                    if this.state.selectedMatchedTxInfo?
                        <SingleTransactionInfo bot={bot} txInfo={this.state.selectedMatchedTxInfo} notMyTransactionClicked={this.notMyTransactionClicked} />
                    else
                        if this.state.anyMatchedTxs
                            <ul id="transaction-confirm-list" className="wide-list">
                                {
                                    for swapId, txInfo of this.state.matchedTxs
                                        <TransactionInfo bot={bot} txInfo={txInfo} clickedFn={this.buildChooseSwapClicked(txInfo)} />
                                }
                            </ul>
                        else
                            <ul id="transaction-wait-list" className="wide-list">
                                <li>
                                    <div className="status-icon icon-pending"></div>
                                    Waiting for <strong>{swapDetails.chosenToken.inAmount} {swapDetails.chosenToken.inAsset}</strong> to be sent to {bot.address}.
                                </li>
                            </ul>
                }



                <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.botUtils.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
            </div>
        </div>


