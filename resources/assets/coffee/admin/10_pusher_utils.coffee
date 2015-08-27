# pusherutils functions
sbAdmin.pusherutils = do ()->
    pusherutils = {}

    pusherutils.subscribeToPusherChanel = (chanelName, callbackFn, pusherUrl=null)->
        # console.log "pusherUrl=",pusherUrl
        if not pusherUrl? then pusherUrl = window.PUSHER_URL
        client = new window.Faye.Client("#{pusherUrl}/public")
        client.subscribe "/#{chanelName}", (data)->
            callbackFn(data)
            return
        return client

    pusherutils.closePusherChanel = (client)->
        client.disconnect()
        return


    return pusherutils
