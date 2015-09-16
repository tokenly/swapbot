# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

exports.handleBotstreamEvents = (botstreamEvents)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS
        botstreamEvents: botstreamEvents
    });
    return


# #############################################
module.exports = exports
