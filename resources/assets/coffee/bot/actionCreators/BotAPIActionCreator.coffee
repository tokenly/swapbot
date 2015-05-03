BotAPIActionCreator = do ()->
    exports = {}

    subscriberId = null

    handleBotstreamEvents = (botstreamEvents)->
        BotstreamEventActions.handleBotstreamEvents(botstreamEvents)

        return

    exports.subscribeToBotstream = (botId)->
        subscriberId = swapbot.pusher.subscribeToPusherChanel "swapbot_botstream_#{botId}", (botstreamEvent)->
            handleBotstreamEvents([botstreamEvent])

        # load all existing botstream events
        $.get "/api/v1/public/boteventstream/#{botId}", (botstreamEvents)=>
            # sort by oldest to newest
            console.log "botstreamEvents",botstreamEvents
            botstreamEvents.sort (a,b)->
                return a.serial - b.serial

            handleBotstreamEvents(botstreamEvents)
            return

        return



    # #############################################
    return exports


