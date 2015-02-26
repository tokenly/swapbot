# pusherutils functions
sbAdmin.pusherutils = do ()->
    pusherutils = {}

    pusherutils.subscribeToPusherChannel = (channelName, callbackFn)->
        client = new window.Faye.Client("#{window.PUSHER_URL}/public")
        client.subscribe "/#{channelName}", (data)->
            callbackFn(data)
            return
        return client

    pusherutils.closePusherChannel = (client)->
        client.disconnect()
        return


    return pusherutils
