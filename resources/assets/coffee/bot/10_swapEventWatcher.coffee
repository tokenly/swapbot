swapEventWatcher = do ()->
    exports = {}

    shouldProcessSwapEvent = (event)->
        return false if not event.swapId?
        switch event.name
            when 'swap.stateChange'
                return false
        return true

    isActive = (event)->
        switch event.name
            when 'unconfirmed.tx', 'swap.confirming', 'swap.failed'
                return true
        return false

    # #############################################

    exports.botEventMatchesSwap = (botEvent, swap)->
        event = botEvent.event
        return false if not event.swapId?
        # console.log "comparing #{event.swapId} == #{swap.id}"
        return (event.swapId == swap.id)

    # returns true if it was applied
    exports.applyEventToSwapEventRecordsIfNew = (botEvent, swapEventRecords)->
        serial = botEvent.serial
        event = botEvent.event
        return false if not shouldProcessSwapEvent(event)

        swapId = event.swapId
        createdAt = botEvent.createdAt

        if not swapEventRecords[swapId]?
            swapEventRecords[swapId] = {
                serial: serial
                date: createdAt
                event: event
                active: isActive(event)
            }
            # console.log "new event for #{swapId}: #{event.name} #{createdAt} (#{serial})"
            return true
        else
            existingEventRecord = swapEventRecords[swapId]
            if serial > existingEventRecord.serial
                swapEventRecords[swapId] = {
                    serial: serial
                    date: createdAt
                    event: event
                    active: isActive(event)
                }
                # console.log "updated event for #{swapId}: #{event.name} #{createdAt} (#{serial})"
                return true

        return false


    # #############################################
    return exports
