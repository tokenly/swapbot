SwapsStore = do ()->
    exports = {}

    allMySwapsById = {}
    allMySwaps = []
    eventEmitter = null

    addNewSwaps = (newSwaps)->
        for swap in newSwaps
            allMySwapsById[swap.id] = swap

        allMySwaps = rebuildAllMySwaps()
        emitChange()

        return

    handleSwapstreamEvents = (eventWrappers)->
        anyChanged = false
        for eventWrapper in eventWrappers
            swapId = eventWrapper.swapUuid
            event = eventWrapper.event
    
            if allMySwapsById[swapId]?
                # update existing swap
                newSwap = buildSwapFromSwapEvent(eventWrapper)
                $.extend(allMySwapsById[swapId], newSwap)
            else
                # new swap
                allMySwapsById[swapId] = buildSwapFromSwapEvent(eventWrapper)

            console.log "eventWrapper=",eventWrapper
            console.log "allMySwapsById[#{swapId}]=",allMySwapsById[swapId]

            anyChanged = true

        if anyChanged
            # rebuild allMySwaps
            allMySwaps = rebuildAllMySwaps()
            # console.log "emitChange"

            emitChange()
        
        return

    rebuildAllMySwaps = ()->
        newAllMySwaps = []
        for id, swap of allMySwapsById
            newAllMySwaps.push(swap)

        # sort by most recent active first
        newAllMySwaps.sort (a,b)->
            return b.serial - a.serial

        return newAllMySwaps

    buildSwapFromSwapEvent = (eventWrapper)->
        newSwap = $.extend({}, eventWrapper.event)
        delete newSwap.name
        newSwap.id = eventWrapper.swapUuid
        newSwap.serial = eventWrapper.serial
        newSwap.updatedAt = eventWrapper.createdAt
        
        if eventWrapper.level >= 200
            newSwap.message = eventWrapper.message
        else
            newSwap.debugMessage = eventWrapper.message

        return newSwap

    emitChange = ()->
        eventEmitter.emitEvent('change')
        return


    # #############################################

    exports.init = ()->
        # init emitter
        eventEmitter = new window.EventEmitter()

        # register with the app dispatcher
        Dispatcher.register (action)->
            switch action.actionType
                when BotConstants.BOT_ADD_NEW_SWAPS
                    addNewSwaps(action.swaps)

                when BotConstants.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS
                    handleSwapstreamEvents(action.swapstreamEvents)

                # else
                #     console.log "unknown action: #{action.actionType}"
            return

        return

    exports.getSwaps = ()->
        return allMySwaps

    exports.getSwapById = (swapId)->
        return null if not allMySwapsById[swapId]?
        return allMySwapsById[swapId]

    exports.addChangeListener = (callback)->
        eventEmitter.addListener('change', callback)
        return

    exports.removeChangeListener = (callback)->
        eventEmitter.removeListener('change', callback)
        return

    # #############################################
    return exports

# a swap is:

# id
# serial
# updatedAt
# message

# destination
# quantityIn
# assetIn
# txidIn
# quantityOut
# assetOut
# txidOut
# confirmations
# state
# isComplete
# isError

