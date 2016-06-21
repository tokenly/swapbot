# ---- begin references
BotConstants     = require '../constants/BotConstants'
Settings         = require '../constants/Settings'
Dispatcher       = require '../dispatcher/Dispatcher'
SwapsStore       = require '../stores/SwapsStore'
GlobalAlertStore = require '../stores/GlobalAlertStore'
# ---- end references

Settings = require '../constants/Settings'

exports = {}

uiState = {
    animatingSwapButtons: [false,false,false,false,false,false,]
    swaps:
        maxSwapsToShow: Settings.SWAPS_TO_SHOW
        maxSwapsRequestedFromServer: Settings.SWAPS_TO_SHOW
        numberOfSwapsLoaded: 0
        loading: false
    globalAlert:
        status: false
        content: ''
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

updateMaxSwapsToShow = ()->
    uiState.swaps.maxSwapsToShow += Settings.MORE_SWAPS_TO_SHOW
    # console.log "uiState.swaps.maxSwapsToShow set to #{uiState.swaps.maxSwapsToShow}"
    emitChange()
    return

swapsLoadingBegin = ()->
    uiState.swaps.loading = true
    emitChange()
    return

swapsLoadingEnd = ()->
    uiState.swaps.loading = false
    emitChange()
    return

updateMaxSwapsRequested = (maxSwapsRequestedFromServer)->
    uiState.swaps.maxSwapsRequestedFromServer = maxSwapsRequestedFromServer
    emitChange()
    return

swapsStoreChanged = ()->
    numberOfSwapsLoaded = SwapsStore.numberOfSwapsLoaded()
    if numberOfSwapsLoaded != uiState.swaps.numberOfSwapsLoaded
        uiState.swaps.numberOfSwapsLoaded = numberOfSwapsLoaded
        emitChange()
    return

globalAlertDataStoreChanged = ()->
    newAlertData = GlobalAlertStore.getCurrentAlertData()
    oldAlertData = uiState.globalAlert
    if alertDataChanged(oldAlertData, newAlertData)
        uiState.globalAlert = newAlertData
        # console.log "alertDataChanged: NEW: ", uiState.globalAlert
        emitChange()
    return

alertDataChanged = (d1, d2)->
    if (d1?.status != d2?.status) then return true
    if (d1?.content != d2?.content) then return true
    return false


# #############################################

exports.init = ()->
    # init emitter
    eventEmitter = new window.EventEmitter()

    # register with the app dispatcher
    Dispatcher.register (action)->
        switch action.actionType

            when BotConstants.UI_BEGIN_SWAPS
                beginSwaps()

            when BotConstants.UI_UPDATE_MAX_SWAPS_TO_SHOW
                updateMaxSwapsToShow()

            when BotConstants.UI_UPDATE_MAX_SWAPS_REQUESTED
                updateMaxSwapsRequested(action.maxSwapsRequestedFromServer)

            when BotConstants.UI_SWAPS_LOADING_BEGIN
                swapsLoadingBegin()

            when BotConstants.UI_SWAPS_LOADING_END
                swapsLoadingEnd()

        return

    SwapsStore.addChangeListener(swapsStoreChanged)
    GlobalAlertStore.addChangeListener(globalAlertDataStoreChanged)

    return

exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

exports.getUIState = ()->
    return uiState

exports.getSwapsUIState = ()->
    return uiState.swaps

exports.updateMaxSwapsRequestedFromServer = (newMaxSwapsRequestedFromServer)->
    uiState.swaps.maxSwapsRequestedFromServer = Math.max(uiState.swaps.maxSwapsRequestedFromServer, newMaxSwapsRequestedFromServer)
    return

# #############################################
module.exports = exports



