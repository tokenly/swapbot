botEventWatcher = do ()->
    exports = {}

    exports.botEventMatchesInAmount = (botEvent, inAmount, inAsset)->
        event = botEvent.event
        switch event.name
            when 'unconfirmed.tx', 'swap.confirming', 'swap.confirmed', 'swap.sent'
                # inQty
                # inAsset
                if event.inQty == inAmount and event.inAsset == inAsset
                    # matched!
                    # console.log "#{event.name}: matched #{inAmount} #{inAsset}"
                    return true
            
        return false

    exports.confirmationsFromEvent = (botEvent)->
        event = botEvent.event
        switch event.name
            when 'unconfirmed.tx'
                return 0
            when 'swap.confirming', 'swap.confirmed', 'swap.sent'
                return event.confirmations

        console.warn "unknown event #{event.name}"        
        return event.confirmations

    exports.txInfoFromBotEvent = (botEvent)->
        event = botEvent.event
        txInfo = {
            name: event.name
            msg: event.msg
            address: event.destination

            swapId: event.swapId

            inAsset: event.inAsset
            inQty: event.inQty
            outAsset: event.outAsset
            outQty: event.outQty

            confirmations: exports.confirmationsFromEvent(botEvent)
            status: event.name
        }
        return txInfo

    # #############################################
    return exports
