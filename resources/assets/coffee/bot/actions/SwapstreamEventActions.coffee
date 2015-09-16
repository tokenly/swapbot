# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

exports.addNewSwaps = (swaps)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_ADD_NEW_SWAPS
        swaps: swaps
    });
    return

exports.handleSwapstreamEvents = (swapstreamEvents)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS
        swapstreamEvents: swapstreamEvents
    });
    return


# #############################################
module.exports = exports
