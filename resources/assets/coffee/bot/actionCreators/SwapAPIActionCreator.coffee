# ---- begin references
SwapstreamEventActions = require '../actions/SwapstreamEventActions'
UserInterfaceActions = require '../actions/UserInterfaceActions'
Settings = require '../constants/Settings'
UserInterfaceStateStore = require '../stores/UserInterfaceStateStore'
swapbot = swapbot or {}; swapbot.pusher = require '../../shared/pusherUtils'
# ---- end references

Settings = require '../constants/Settings'

exports = {}

subscriberId = null
loading = false

handleSwapstreamEvents = (swapstreamEvents)->
    SwapstreamEventActions.handleSwapstreamEvents(swapstreamEvents)

    return

loadSwapsFromAPI = (botId)->
    $.get "/api/v1/public/swaps/#{botId}", (swapsData)->
        SwapstreamEventActions.addNewSwaps(swapsData)
        return
    return

loadSwapstreamEventsFromAPI = (botId, limit)->
    loading = true

    # update requested amount immediately
    UserInterfaceStateStore.updateMaxSwapsRequestedFromServer(limit)

    setTimeout ()->
        # console.log "beginLoadingMoreSwaps"
        UserInterfaceActions.beginLoadingMoreSwaps()
        return
    , 1

    $.get "/api/v1/public/swapevents/#{botId}?latestperswap=1&limit=#{limit}&sort=serial desc", (swapstreamEvents)=>
        handleSwapstreamEvents(swapstreamEvents)

        UserInterfaceActions.endLoadingMoreSwaps()

        loading = false
        return

    return

subscribeToSwapstream = (botId)->
    # console.log "exports.subscribeToSwapstream"

    onSubscribedToSwapstream = ()->
        # load all recent swapstream events
        loadSwapstreamEventsFromAPI(botId, Settings.SWAPS_TO_SHOW)
        return

    onSwapstreamEvent = (swapstreamEvent)->
        # console.log "swapstreamEvent heard: ",swapstreamEvent
        handleSwapstreamEvents([swapstreamEvent])
        return


    subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_swapstream_#{botId}", onSwapstreamEvent, onSubscribedToSwapstream)

    return

onUIChange = (botId)->
    maxSwapsToShow = UserInterfaceStateStore.getSwapsUIState().maxSwapsToShow
    if maxSwapsToShow > UserInterfaceStateStore.getSwapsUIState().maxSwapsRequestedFromServer
        if not loading
            loadSwapstreamEventsFromAPI(botId, maxSwapsToShow)
    return

# #############################################

exports.init = (botId)->
    subscribeToSwapstream(botId)

    # listen for ui state updates
    UserInterfaceStateStore.addChangeListener ()->
        onUIChange(botId)
        return

    return


# #############################################
module.exports = exports
