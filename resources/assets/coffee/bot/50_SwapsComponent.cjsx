SwapStatus = React.createClass
    displayName: 'SwapStatus'

    getInitialState: ()->
        return {
        }

    componentDidMount: ()->
        return

    render: ->
        swapEventRecord = this.props.swapEventRecord
        return swapEventRenderer.renderSwapStatus(this.props.bot, this.props.swap, this.props.swapEventRecord)



SwapsList = React.createClass
    displayName: 'SwapsList'

    getInitialState: ()->
        return {
        }

    componentDidMount: ()->
        return

    render: ->
        bot = this.props.bot

        if bot.swaps
            <ul id="swaps-list" className="wide-list">
            {
                for swap in bot.swaps
                    <li>
                        <div>
                            <div className="item-header">{ swap.out } <small>({bot.balances[swap.out]} available)</small></div>
                            <p>Sends { swapbot.swapUtils.exchangeDescription(swap) }.</p>
                            <a href="{ bot.id }/popup" target="_blank" className="icon-next"></a>
                        </div>
                    </li>
            }
            </ul>
        else
            <p className="description">There are no swaps available.</p>


SwapStatuses = React.createClass
    displayName: 'SwapStatuses'

    getInitialState: ()->
        return {
            swaps: null
            swapEventRecords: {}
        }

    componentDidMount: ()->
        bot = this.props.bot
        botId = bot.id

        $.when(
            $.ajax("/api/v1/public/swaps/#{botId}"),
            $.ajax("/api/v1/public/botevents/#{botId}")
        ).done (r2, r3)=>
            if this.isMounted()
                swapsData = r2[0]
                eventsData = r3[0]

                this.setState({swaps: swapsData})

                for botEvent in eventsData
                    this.applyBotEventToSwaps(botEvent)

                # subscribe to bot events
                this.subscribeToPusher(bot)
            return
        return

    componentWillUnmount: ()->
        swapbot.pusher.closePusherChanel(this.state.pusherClient) if this.state.pusherClient
        return

    subscribeToPusher: (bot)->
        swapbot.pusher.subscribeToPusherChanel "swapbot_events_#{bot.id}", (botEvent)=>
            this.applyBotEventToSwaps(botEvent)

        return

    applyBotEventToSwaps: (botEvent)->
        return false if not this.state.swaps?

        newSwapEventRecords = this.state.swapEventRecords
        anyFound = false
        for swap in this.state.swaps
            if swapEventWatcher.botEventMatchesSwap(botEvent, swap)
                applied = swapEventWatcher.applyEventToSwapEventRecordsIfNew(botEvent, newSwapEventRecords)
                anyFound = true if applied
        
        if anyFound
            # console.log "applyBotEventToSwaps anyFound=#{anyFound}"
            this.setState({swapEventRecords: newSwapEventRecords})

        return anyFound

    activeSwaps: (fn)->
        eventRecords = this.state.swapEventRecords
        renderedSwaps = for swap in this.state.swaps
            eventRecord = eventRecords[swap.id]
            if eventRecord?.active
                fn(swap, eventRecord)
        return renderedSwaps

    recentSwaps: (fn)->
        eventRecords = this.state.swapEventRecords
        renderedSwaps = for swap in this.state.swaps
            eventRecord = eventRecords[swap.id]
            console.log "#{swap.id} eventRecord=",eventRecord
            if eventRecord? and not eventRecord.active
                fn(swap, eventRecord)
        return renderedSwaps

    render: ->
        if not this.state.swaps
            return <div>No swaps</div>
        
        anyActiveSwaps = false
        anyRecentSwaps = false

        return <div>
            <div id="active-swaps" className="section grid-100">
                <h3>Active Swaps</h3>
                <ul className="swap-list">
                    {
                        this.activeSwaps (swap, eventRecord)=>
                            anyActiveSwaps = true
                            <SwapStatus key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
                    }
                </ul>
                {
                    if not anyActiveSwaps
                        <div className="description">No Active Swaps</div>
                }
            </div>
            <div className="clearfix"></div>
            <div id="recent-swaps" className="section grid-100">
                <h3>Recent Swaps</h3>
                <ul className="swap-list">
                    {
                        this.recentSwaps (swap, eventRecord)=>
                            anyRecentSwaps = true
                            <SwapStatus key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
                    }
                </ul>
                {
                    if not anyRecentSwaps
                        <div className="description">No Active Swaps</div>
                }

                <div style={textAlign: 'center'}>
                    <button className="button-load-more">Load more swaps...</button>
                </div>
            </div>
        </div>



        # <h3>Recent Swaps</h3>
        # <ul className="swap-list">
        #     <li className="confirmed">
        #         <div className="status-icon icon-confirmed"></div>
        #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
        #     </li>
        #     <li className="failed">
        #         <div className="status-icon icon-failed"></div>
        #         Failed to process <b>100,000 UNKNOWNCOIN</b>.
        #     </li>
        #     <li className="confirmed">
        #         <div className="status-icon icon-confirmed"></div>
        #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
        #     </li>
        #     <li className="confirmed">
        #         <div className="status-icon icon-confirmed"></div>
        #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
        #     </li>
        #     <li className="confirmed">
        #         <div className="status-icon icon-confirmed"></div>
        #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
        #     </li>
        #     <li className="failed">
        #         <div className="status-icon icon-failed"></div>
        #         Failed to process <b>100,000 UNKNOWNCOIN</b>.
        #     </li>
        #     <li className="confirmed">
        #         <div className="status-icon icon-confirmed"></div>
        #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
        #     </li>
        # </ul>