# pusherutils functions
pusherutils = {}

MISSING_PUSHER_ERROR_SEEN = null

pusherutils.subscribeToPusherChanel = (chanelName, callbackFn, pusherUrl=null)->
    if not window.Faye?
        console.error('Pusher client not defined')

        if not MISSING_PUSHER_ERROR_SEEN
            alert('Unable to load the SwapBot data feed.  Please reload this page.')
            MISSING_PUSHER_ERROR_SEEN = true
            if window.Bugsnag?
                Bugsnag.notify("Missing Pusher Reference", "window.Faye was not a valid object");

        return null

    # console.log "pusherUrl=",pusherUrl
    if not pusherUrl? then pusherUrl = window.PUSHER_URL
    client = new window.Faye.Client("#{pusherUrl}/public")
    client.subscribe "/#{chanelName}", (data)->
        callbackFn(data)
        return
    return client

pusherutils.closePusherChanel = (client)->
    if client?
        client.disconnect()
    return


module.exports = pusherutils
