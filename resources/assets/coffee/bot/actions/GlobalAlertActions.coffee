# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

exports.handleAlertUpdate = (alertData)->
    Dispatcher.dispatch({
        actionType: BotConstants.GLOBAL_ALERT_UPDATED
        alertData: alertData
    });
    return


# #############################################
module.exports = exports
