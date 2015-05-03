BotstreamEventActions = do ()->
    exports = {}

    exports.handleBotstreamEvents = (botstreamEvents)->
        Dispatcher.dispatch({
            actionType: BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS
            botstreamEvents: botstreamEvents
        });
        return
    

    # #############################################
    return exports
