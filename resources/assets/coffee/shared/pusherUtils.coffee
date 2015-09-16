# swapbot functions


exports = {}

# pusherURL is optional
exports.subscribeToPusherChanel = (channelName, dataCallbackFn, onSubscribedFn=null, pusherURL=null)->
    if not pusherURL?
        pusherURL = window.PUSHER_URL


    client = new window.Faye.Client("#{pusherURL}/public")
    subscription = client.subscribe "/#{channelName}", (data)->
        dataCallbackFn(data)
        return

    subscription.then ()->
        if onSubscribedFn?
            onSubscribedFn()
        return


    return client

exports.closePusherChanel = (client)->
    client.disconnect()
    return


module.exports = exports
