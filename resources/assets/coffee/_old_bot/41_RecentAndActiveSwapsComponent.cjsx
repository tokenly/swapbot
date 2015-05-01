
# ############################################################################################################
# An entry in the active or recent swaps list

RecentOrActiveSwapComponent = React.createClass
    displayName: 'RecentOrActiveSwapComponent'

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
# The list of all recent or active swaps

RecentAndActiveSwapsComponent = React.createClass
    displayName: 'RecentAndActiveSwapsComponent'

    getInitialState: ()->
        return {
            swaps: []
            swapIdsMap: {}
            swapEventRecords: {}
        }


    componentDidMount: ()->
        this.refreshSwapsData ()=>
            this.subscriberId = this.props.eventSubscriber.subscribe (botEvent)=>
                this.applyBotEventToSwaps(botEvent)
                return
            return
        return

    componentWillUnmount: ()->
        if this.subscriberId?
            this.props.eventSubscriber.unsubscribe(this.subscriberId)
            this.subscriberId = null
        return

    refreshSwapsData: (callback)->
        # refresh the swaps once
        swapbot.fnUtils.callOnceWithCallback 'K:rswp', ((cb)=> this._refreshSwapsFn(cb)), callback
        return


    _refreshSwapsFn: (refreshCallback)->
        botId = this.props.bot.id

        $.when(
            $.ajax("/api/v1/public/swaps/#{botId}"),
        ).done (swapsData)=>
            if this.isMounted()
                newSwapIdsMap = $.extend({}, this.state.swapIdsMap)

                anyFound = false
                for newSwap in swapsData
                    if not newSwapIdsMap[newSwap.id]?
                        anyFound = true
                        newSwapIdsMap[newSwap.id] = true

                if anyFound
                    console.log "newSwapIdsMap=",newSwapIdsMap
                    this.setState({swaps: swapsData, swapIdsMap: newSwapIdsMap})

                if refreshCallback?
                    refreshCallback()
            return
        return



    applyBotEventToSwaps: (botEvent, allowRecursion=true)->
        return false if not this.state.swaps?

        # console.log "botEvent=",botEvent
        # console.log "old this.state.swapEventRecords",this.state.swapEventRecords
        newSwapEventRecords = $.extend({}, this.state.swapEventRecords)
        anyFound = false
        for swap in this.state.swaps
            if swapEventWatcher.botEventMatchesSwap(botEvent, swap)
                applied = swapEventWatcher.applyEventToSwapEventRecordsIfNew(botEvent, newSwapEventRecords)
                anyFound = true if applied

        
        if anyFound
            # console.log "new newSwapEventRecords",newSwapEventRecords
            # sort swaps by most recent first
            sortedSwaps = this.state.swaps.slice(0)
            sortedSwaps.sort (a,b)->
                aSerial = newSwapEventRecords[a.id]?.serial
                bSerial = newSwapEventRecords[b.id]?.serial
                aSerial = 0 if not aSerial?
                bSerial = 0 if not bSerial?
                return bSerial - aSerial

            this.setState({swapEventRecords: newSwapEventRecords, swaps: sortedSwaps})

        if not anyFound and botEvent.event.swapId?
            if not this.state.swapIdsMap[botEvent.event.swapId]?
                console.log "did not find swapId: #{botEvent.event.swapId}.  refreshing swapsData"
                # this is for a new swap
                this.refreshSwapsData ()=>
                    # apply again
                    if allowRecursion
                        this.applyBotEventToSwaps(botEvent, false)
                    return

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
                            <RecentOrActiveSwapComponent key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
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
                            <RecentOrActiveSwapComponent key={swap.id} bot={this.props.bot} swap={swap} swapEventRecord={eventRecord} />
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


