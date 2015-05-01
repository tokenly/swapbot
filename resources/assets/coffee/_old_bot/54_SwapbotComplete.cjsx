
# ########################################################################################################################


SwapbotComplete = React.createClass
    displayName: 'SwapbotComplete'
    subscriberId: null

    componentDidMount: ()->
        this.subscriberId = this.props.eventSubscriber.subscribe (botEvent)=>
                if botEventWatcher.botEventIsFinal(botEvent)
                    matchedTxInfo = botEventWatcher.txInfoFromBotEvent(botEvent)
                    this.setState({matchedTxInfo: matchedTxInfo})
            return
        return

    componentWillUnmount: ()->
        if this.subscriberId?
            this.props.eventSubscriber.unsubscribe(this.subscriberId)
            this.subscriberId = null
        return


    getInitialState: ()->
        return {
            matchedTxInfo: null
            success: true
        }

    render: ()->
        bot = this.props.bot
        swapDetails = this.props.swapDetails
        swap = swapDetails.swap

        return <div id="swapbot-container" className="section grid-100">
            <div id="swap-step-4" className="content hidden">
                <h2>Successfully finished</h2>
                <div className="x-button" id="swap-step-4-close"></div>
                <div className="segment-control">
                    <div className="line"></div>
                    <br>
                    <div className="dot"></div>
                    <div className="dot"></div>
                    <div className="dot"></div>
                    <div className="dot selected"></div>
                </div>
                <div className="icon-success center"></div>
                <p>Exchanged <b>0.1 XXX</b> for <b>100,000 XXXX</b> with {bot.address}.</p>
                <p><a href={"/public/#{bot.username}/swap/#{swap.id}"} className="details-link" target="_blank">Transaction details <i className="fa fa-arrow-circle-right"></i></a></p>
            </div>
        </div>


