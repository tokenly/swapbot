# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

exports.addNewQuote = (quote)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_ADD_NEW_QUOTE
        quote: quote
    });
    return



# #############################################
module.exports = exports
