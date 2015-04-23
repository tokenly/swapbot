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
        }

    componentDidMount: ()->
        return

    render: ->
        swapEventRecord = this.props.swapEventRecord
        return swapEventRenderer.renderSwapStatus(this.props.bot, this.props.swap, this.props.swapEventRecord)

# ############################################################################################################

# SwapsListComponent = React.createClass
#     displayName: 'SwapsListComponent'

#     getInitialState: ()->
#         return {
#         }

#     componentDidMount: ()->
#         return

#     buildChooseSwap: (swap)->
#         return ()=>
#             this.props.chosenSwapProvider.setSwap(swap)
#             return

#     render: ->
#         bot = this.props.bot

#         if bot.swaps
#             <ul id="swaps-list" className="wide-list">
#             {
#                 for swap, index in bot.swaps
#                     <li key={"swap#{index}"} className="swap">
#                         <div>
#                             <div className="item-header">{ swap.out } <small>({bot.balances[swap.out]} available)</small></div>
#                             <p>Sends { swapbot.swapUtils.exchangeDescription(swap) }.</p>
#                             <a href="#choose-swap" onClick={this.buildChooseSwap(swap)} className="icon-next"></a>
#                         </div>
#                     </li>
#             }
#             </ul>
#         else
#             <p className="description">There are no swaps available.</p>

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
        ).done (r2)=>
            if this.isMounted()
                swapsData = r2[0]

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