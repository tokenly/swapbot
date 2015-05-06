SwapAPIActionCreator = do ()->
    exports = {}

    subscriberId = null

    handleSwapstreamEvents = (swapstreamEvents)->
        SwapstreamEventActions.handleSwapstreamEvents(swapstreamEvents)

        return

    exports.loadSwapsFromAPI = (botId)->
        $.get "/api/v1/public/swaps/#{botId}", (swapsData)->
            SwapstreamEventActions.addNewSwaps(swapsData)
            return
        return

    exports.subscribeToSwapstream = (botId)->
        # console.log "exports.subscribeToSwapstream"
        subscriberId = swapbot.pusher.subscribeToPusherChanel "swapbot_swapstream_#{botId}", (swapstreamEvent)->
            # console.log "swapstreamEvent heard: ",swapstreamEvent
            handleSwapstreamEvents([swapstreamEvent])
            return

        # load all existing swapstream events
        $.get "/api/v1/public/swapevents/#{botId}", (swapstreamEvents)=>
            # sort by oldest to newest
            swapstreamEvents.sort (a,b)->
                return a.serial - b.serial

            handleSwapstreamEvents(swapstreamEvents)
            return

        return



    # #############################################
    return exports


