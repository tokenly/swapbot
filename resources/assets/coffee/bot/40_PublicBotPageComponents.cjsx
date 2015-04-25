BotStatusComponent = React.createClass
    displayName: 'BotStatusComponent'

    getInitialState: ()->
        return {
        }

    componentDidMount: ()->
        this.props.eventSubscriber.subscribe (botEvent)=>
            newState = swapbot.botUtils.newBotStatusFromEvent(this.state.botStatus, botEvent)
            this.setState({botStatus: newState})
        return

    render: ->
        <div>
            {
                if this.state.botStatus == 'active'
                    <div><div className="status-dot bckg-green"></div>Active</div>
                else
                    <div><div className="status-dot bckg-red"></div>Inactive</div>
            }
            <button className="button-question"></button>
        </div>


# ############################################################################################################

SwapStatusComponent = React.createClass
    displayName: 'SwapStatusComponent'

    getInitialState: ()->
        return {
            fromNow: null
        }

    componentDidMount: ()->
        this.updateNow()

        this.intervalTimer = setInterval ()=>
            this.updateNow()
        , 1000

        return

    updateNow: ()->
        this.setState({fromNow: moment(this.props.swapEventRecord.date).fromNow()})
        return

    componentWillUnmount: ()->
        if this.intervalTimer?
            clearInterval(this.intervalTimer)
        return

    render: ->
        swapEventRecord = this.props.swapEventRecord
        return swapEventRenderer.renderSwapStatus(this.props.bot, this.props.swap, this.props.swapEventRecord, this.state.fromNow)

# ############################################################################################################

RecentAndActiveSwapsComponent = React.createClass
    displayName: 'RecentAndActiveSwapsComponent'

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
        ).done (swapsData)=>
            if this.isMounted()
                console.log "swapsData=",swapsData

                this.setState({swaps: swapsData})

                this.props.eventSubscriber.subscribe (botEvent)=>
                    this.applyBotEventToSwaps(botEvent)
            return
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
            # sort swaps by most recent first
            sortedSwaps = this.state.swaps.slice(0)
            sortedSwaps.sort (a,b)->
                aSerial = newSwapEventRecords[a.id]?.serial
                bSerial = newSwapEventRecords[b.id]?.serial
                aSerial = 0 if not aSerial?
                bSerial = 0 if not bSerial?
                return bSerial - aSerial

            this.setState({swapEventRecords: newSwapEventRecords, swaps: sortedSwaps})

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
            # console.log "#{swap.id} eventRecord=",eventRecord
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
                            <SwapStatusComponent key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
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
                            <SwapStatusComponent key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
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