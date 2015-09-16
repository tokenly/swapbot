# ---- begin references
BotstreamEventActions = require '../actions/BotstreamEventActions'
swapbot = swapbot or {}; swapbot.pusher = require '../../shared/pusherUtils'
# ---- end references

exports = {}

subscriberId = null

handleBotstreamEvents = (botstreamEvents)->
    BotstreamEventActions.handleBotstreamEvents(botstreamEvents)

    return

exports.subscribeToBotstream = (botId)->
    onSubscribedToBotstream = ()->
        # load all existing botstream events
        $.get "/api/v1/public/boteventstream/#{botId}?sort=serial desc&limit=1", (botstreamEvents)=>
            handleBotstreamEvents(botstreamEvents)
            return
        return

    onBotstreamEvent = (botstreamEvent)->
        handleBotstreamEvents([botstreamEvent])
        return

    subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_botstream_#{botId}", onBotstreamEvent, onSubscribedToBotstream)

    return



# #############################################
module.exports = exports


