# ---- begin references
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}
eventEmitter = null

storedBots = {}


emitChange = ()->
    eventEmitter.emitEvent('change')
    return


updateBot = (newBot)->
    storedBots[newBot.id] = newBot
    emitChange()
    return

# #############################################

exports.init = (bot)->
    # init emitter
    eventEmitter = new window.EventEmitter()

    # # register with the app dispatcher
    # Dispatcher.register (action)->
    #     return

    storedBots[bot.id] = bot

    return

exports.getBot = (botId)->
    return storedBots[botId]

exports.updateBot = (newBot)->
    updateBot(newBot)
    return


exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports
