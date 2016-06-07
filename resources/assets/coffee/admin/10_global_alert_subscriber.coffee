# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.pusherutils = require './10_pusher_utils'
# ---- end references

# globalAlertSubscriber functions
currentAlertData = null
changeListeners = {}
changeListenerID = 0

globalAlertSubscriber = {}

handleAlertUpdate = (alertData)->
    currentAlertData = alertData
    for id, changeListenerCallback of changeListeners
        changeListenerCallback(currentAlertData)
    
    return


# subscribe
globalAlertSubscriber.initSubscriber = ()->
    sbAdmin.api.getGlobalAlert().then(
        (alertData)->
            handleAlertUpdate(alertData)
        , (errorResponse)->
            console.error "Global Alert API fetch failed. ", errorResponse
            return
    )


    # also subscribe to the pusher events
    pusherClient = sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_alerts", handleAlertUpdate)

    return




globalAlertSubscriber.addChangeListener = (changeListenerCallback)->
    changeListeners[++changeListenerID] = changeListenerCallback
    if currentAlertData?
        changeListenerCallback(currentAlertData.last, currentAlertData)
    return changeListenerID

globalAlertSubscriber.removeChangeListener = (id)->
    delete changeListeners[id]
    return



module.exports = globalAlertSubscriber

