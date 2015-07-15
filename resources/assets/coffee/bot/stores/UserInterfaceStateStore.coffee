UserInterfaceStateStore = do ()->
    exports = {}

    uiState = {
        animatingSwapButtons: [false,false,false,false,false,false,]
    }
    
    eventEmitter = null


    emitChange = ()->
        eventEmitter.emitEvent('change')
        return

    # #############################################

    beginSwaps = ()->
        # do up to 6 buttons
        delay = 75
        hold = 150
        for i in [0...6]
            do (i)->
                setTimeout ()->
                    uiState.animatingSwapButtons[i] = true
                    emitChange()
                , (i * delay)

                setTimeout ()->
                    uiState.animatingSwapButtons[i] = false
                    emitChange()
                , (delay + hold + (i * delay))
        return

    # #############################################

    exports.init = ()->
        # init emitter
        eventEmitter = new window.EventEmitter()

        # register with the app dispatcher
        Dispatcher.register (action)->
            switch action.actionType

                when BotConstants.UI_BEGIN_SWAPS
                    beginSwaps()

            return
        return

    exports.addChangeListener = (callback)->
        eventEmitter.addListener('change', callback)
        return

    exports.removeChangeListener = (callback)->
        eventEmitter.removeListener('change', callback)
        return

    exports.getUIState = ()->
        return uiState

    # #############################################
    return exports



