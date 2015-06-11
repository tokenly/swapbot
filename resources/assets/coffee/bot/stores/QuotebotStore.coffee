QuotebotStore = do ()->
    exports = {}

    eventEmitter = null
    currentQuote = null

    addNewQuote = (newQuote)->
        currentQuote = newQuote
        emitChange()
        return

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
                when BotConstants.BOT_ADD_NEW_QUOTE
                    addNewQuote(action.quote)
            return

        return

    exports.getCurrentQuote = ()->
        return currentQuote

    exports.getCurrentPrice = ()->
        if not currentQuote? then return null
        return currentQuote.last

    exports.addChangeListener = (callback)->
        eventEmitter.addListener('change', callback)
        return

    exports.removeChangeListener = (callback)->
        eventEmitter.removeListener('change', callback)
        return

    # #############################################
    return exports

