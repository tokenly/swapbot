BotstreamStore = do ()->
    exports = {}

    allMyBotstreamEventsById = {}
    allMyBotstreamEvents = []
    eventEmitter = null

    handleBotstreamEvents = (eventWrappers)->
        anyChanged = false
        for eventWrapper in eventWrappers
            eventId = eventWrapper.id
            event = eventWrapper.event
    
            if allMyBotstreamEventsById[eventId]?
                # update existing event
                allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper)
            else
                # new event
                allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper)

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
        return newEvent

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
                when BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS
                    handleBotstreamEvents(action.botstreamEvents)

                # else
                #     console.log "unknown action: #{action.actionType}"
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
    return exports


# a event is:

# txid
# confirmations
# state
# isActive
