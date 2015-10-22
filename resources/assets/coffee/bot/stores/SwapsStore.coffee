# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

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
            existingSwap = allMySwapsById[swapId]
            if eventWrapper.serial > existingSwap.serial
                newSwap = buildSwapFromSwapEvent(eventWrapper)
                $.extend(allMySwapsById[swapId], newSwap)
            else
                # merge the existing newer one on top of the just-received old one
                #   so the newest event always takes precidence
                newSwap = buildSwapFromSwapEvent(eventWrapper)
                allMySwapsById[swapId] = $.extend({}, newSwap, allMySwapsById[swapId])
                allMySwapsById[swapId].createdAt = eventWrapper.createdAt
        else
            # new swap
            allMySwapsById[swapId] = buildSwapFromSwapEvent(eventWrapper)
            allMySwapsById[swapId].createdAt = eventWrapper.createdAt

        anyChanged = true

    if anyChanged
        # rebuild allMySwaps
        allMySwaps = rebuildAllMySwaps()
        emitChange()
    
    return

rebuildAllMySwaps = ()->
    newAllMySwaps = []
    for id, swap of allMySwapsById
        newAllMySwaps.push(swap)

    # sort by most recently created first
    newAllMySwaps.sort (a,b)->
        # return b.serial - a.serial
        # return b.createdAt - a.createdAt
        bDate = new Date(b.createdAt)
        aDate = new Date(a.createdAt)
        return bDate - aDate

    return newAllMySwaps

buildSwapFromSwapEvent = (eventWrapper)->
    newSwap = $.extend({}, eventWrapper.event)
    delete newSwap.name
    newSwap.id = eventWrapper.swapUuid
    newSwap.serial = eventWrapper.serial
    newSwap.updatedAt = eventWrapper.createdAt

    # merge completedAt
    if eventWrapper.event.completedAt?
        newSwap.completedAt = eventWrapper.event.completedAt * 1000
    
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

        return

    return

exports.getSwaps = ()->
    return allMySwaps

exports.numberOfSwapsLoaded = ()->
    return allMySwaps.length

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
module.exports = exports

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

# type (for completed swaps)
