# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

eventEmitter = null
currentQuotes = {}

addNewQuote = (newQuote)->
    currentQuotes[newQuote.source+'.'+newQuote.pair] = newQuote
    console.log "currentQuotes updated with #{newQuote.source+'.'+newQuote.pair}"
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

exports.getCurrentQuotes = ()->
    return currentQuotes

exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports

