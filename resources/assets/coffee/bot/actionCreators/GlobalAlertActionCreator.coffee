# ---- begin references
GlobalAlertActions = require '../actions/GlobalAlertActions'
UserInterfaceActions = require '../actions/UserInterfaceActions'
Settings = require '../constants/Settings'
UserInterfaceStateStore = require '../stores/UserInterfaceStateStore'
swapbot = swapbot or {}; swapbot.pusher = require '../../shared/pusherUtils'
# ---- end references

Settings = require '../constants/Settings'

exports = {}

subscriberId = null
loading = false

handleAlertUpdate = (alertData)->
    GlobalAlertActions.handleAlertUpdate(alertData)

    return

loadAlertFromAPI = ()->
    loading = true
    $.get "/api/v1/globalalert", (alertData)=>
        handleAlertUpdate(alertData)
        loading = false
        return

    return

# #############################################

exports.init = ()->
    onAlertChange = (alertData)->
        handleAlertUpdate(alertData)
        return

    subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_alerts", handleAlertUpdate, loadAlertFromAPI)

    return

# #############################################

module.exports = exports
