# pusherutils functions
sbAdmin.pusherutils = do ()->
    pusherutils = {}

    pusherutils.subscribeToPusherChanel = (chanelName, callbackFn)->
        client = new window.Faye.Client("#{window.PUSHER_URL}/public")
        client.subscribe "/#{chanelName}", (data)->
            callbackFn(data)
            return
        return client

    pusherutils.closePusherChanel = (client)->
        client.disconnect()
        return


    return pusherutils
