# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
BotStore = require '../stores/BotStore'
# ---- end references

exports = {}

allMyBotstreamEventsById = {}
allMyBotstreamEvents = []
eventEmitter = null

handleBotstreamEvents = (eventWrappers)->
    anyChanged = false
    for eventWrapper in eventWrappers
        if eventWrapper.isBotUpdate
            # handle bot updates differently
            handleBotUpdate(eventWrapper)
            continue

        eventId = eventWrapper.id
        event = eventWrapper.event

        if allMyBotstreamEventsById[eventId]?
            # update existing bot event
            existingEvent = allMyBotstreamEventsById[eventId]
            if eventWrapper.serial > existingEvent.serial
                allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper)
            else
                # merge the existing newer one on top of the just-received old one
                #   so the newest event always takes precidence
                newBotEvent = buildEventFromStreamstreamEventWrapper(eventWrapper)
                allMyBotstreamEventsById[eventId] = $.extend({}, newBotEvent, allMyBotstreamEventsById[eventId])
        else
            # new event
            allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper)

        # console.log "BotstreamStore allMyBotstreamEventsById[#{eventId}] = ",allMyBotstreamEventsById[eventId]

        anyChanged = true

    if anyChanged
        # rebuild allMyBotstreamEvents
        allMyBotstreamEvents = rebuildAllMyBotEvents()

        emitChange()
    
    return

rebuildAllMyBotEvents = ()->
    newAllMyBotstreamEvents = []
    for id, event of allMyBotstreamEventsById
        newAllMyBotstreamEvents.push(event)
    return newAllMyBotstreamEvents

buildEventFromStreamstreamEventWrapper = (eventWrapper)->
    newEvent = $.extend({}, eventWrapper.event)
    delete newEvent.name
    newEvent.id        = eventWrapper.id
    newEvent.serial    = eventWrapper.serial
    newEvent.updatedAt = eventWrapper.createdAt
    newEvent.message   = eventWrapper.message
    if eventWrapper.level >= 200
        newEvent.message = eventWrapper.message
    else
        newEvent.debugMessage = eventWrapper.message
    return newEvent

emitChange = ()->
    eventEmitter.emitEvent('change')
    return

handleBotUpdate = (eventWrapper)->
    newBotData = eventWrapper.bot
    BotStore.updateBot(newBotData)
    return


# #############################################

exports.init = ()->
    # init emitter
    eventEmitter = new window.EventEmitter()

    # register with the app dispatcher
    Dispatcher.register (action)->
        switch action.actionType
            when BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS
                handleBotstreamEvents(action.botstreamEvents)
        return

    return

exports.getEvents = ()->
    return allMyBotstreamEvents

exports.getLastEvent = ()->
    return null if not allMyBotstreamEvents.length > 1
    return allMyBotstreamEvents[allMyBotstreamEvents.length - 1]

exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports


# a event is:

# txid
# confirmations
# state
# isActive
