UserInterfaceActions = window.UserInterfaceActions = do ()->
    exports = {}

    exports.beginSwaps = ()->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_BEGIN_SWAPS
        })
        return

    # #############################################
    return exports
