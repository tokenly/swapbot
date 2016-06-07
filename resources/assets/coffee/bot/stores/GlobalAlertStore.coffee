# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

eventEmitter = null
currentAlertData = null

addAlertData = (newAlertData)->
    console.log "newAlertData: ", newAlertData
    currentAlertData = newAlertData
    emitChange()
    return

emitChange = ()->
    eventEmitter.emitEvent('change')
    return



# #############################################

exports.init = ()->
    console.log "GlobalAlertStore.init()"

    # init emitter
    eventEmitter = new window.EventEmitter()

    # register with the app dispatcher
    Dispatcher.register (action)->
        switch action.actionType
            when BotConstants.GLOBAL_ALERT_UPDATED
                addAlertData(action.alertData)
        return

    return

exports.getCurrentAlertData = ()->
    return currentAlertData

exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports

