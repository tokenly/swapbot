# swapbot functions
swapbot = {} if not swapbot?

swapbot.pusher = do ()->
    exports = {}

    # pusherURL is optional
    exports.subscribeToPusherChanel = (pusherURL, channelName, callbackFn)->
        if not callbackFn?
            callbackFn = channelName
            channelName = pusherURL
            pusherURL = window.PUSHER_URL


        client = new window.Faye.Client("#{pusherURL}/public")
        client.subscribe "/#{channelName}", (data)->
            callbackFn(data)
            return
        return client

    exports.closePusherChanel = (client)->
        client.disconnect()
        return


    return exports
