# swapbot functions
swapbot = {} if not swapbot?

swapbot.pusher = do ()->
    exports = {}

    exports.subscribeToPusherChanel = (chanelName, callbackFn)->
        client = new window.Faye.Client("#{window.PUSHER_URL}/public")
        client.subscribe "/#{chanelName}", (data)->
            callbackFn(data)
            return
        return client

    exports.closePusherChanel = (client)->
        client.disconnect()
        return


    return exports
