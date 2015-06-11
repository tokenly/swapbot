QuotebotEventActions = do ()->
    exports = {}

    exports.addNewQuote = (quote)->
        Dispatcher.dispatch({
            actionType: BotConstants.BOT_ADD_NEW_QUOTE
            quote: quote
        });
        return

    

    # #############################################
    return exports
