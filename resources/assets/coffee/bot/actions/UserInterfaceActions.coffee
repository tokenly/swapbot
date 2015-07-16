UserInterfaceActions = window.UserInterfaceActions = do ()->
    exports = {}

    exports.beginSwaps = ()->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_BEGIN_SWAPS
        })
        return

    exports.updateMaxSwapsToShow = ()->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_UPDATE_MAX_SWAPS_TO_SHOW
        })
        return

    exports.beginLoadingMoreSwaps = ()->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_SWAPS_LOADING_BEGIN
        })
        return

    exports.endLoadingMoreSwaps = ()->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_SWAPS_LOADING_END
        })
        return

    exports.updateMaxSwapsRequestedFromServer = (maxSwapsRequestedFromServer)->
        Dispatcher.dispatch({
            actionType: BotConstants.UI_SWAPS_LOADING_END
            maxSwapsRequestedFromServer: maxSwapsRequestedFromServer
        })
        return

    # #############################################
    return exports
