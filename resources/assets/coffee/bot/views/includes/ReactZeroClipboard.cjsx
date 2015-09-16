ReactZeroClipboard = undefined

client = undefined

# callbacks waiting for ZeroClipboard to load
waitingForScriptToLoad = []

eventHandlers = 
    copy: []
    afterCopy: []
    error: []
    ready: []

propToEvent = 
    onCopy: 'copy'
    onAfterCopy: 'afterCopy'
    onError: 'error'
    onReady: 'ready'

readyEventHasHappened = false
# asynchronusly load ZeroClipboard from cdnjs
# it should automatically discover the SWF location using some clever hacks :-)

handleZeroClipLoad = (error) ->
    # grab it and free up the global
    ZeroClipboard = window.ZeroClipboard
    delete window.ZeroClipboard
    client = new ZeroClipboard

    handleEvent = (eventName) ->
        client.on eventName, (event) ->
            # ready has no active element
            if eventName == 'ready'
                eventHandlers['ready'].forEach (xs) ->
                    xs[1] event
                    return
                readyEventHasHappened = true
                return

            activeElement = ZeroClipboard.activeElement()

            # find an event handler for this element
            # we use some so we don't continue looking after a match is found
            eventHandlers[eventName].some (xs) ->
                element = xs[0]
                callback = xs[1]
                if element == activeElement
                    callback event
                    return true
                return
            return
        return

    # init the events
    for eventName of eventHandlers
        handleEvent eventName

    # call the callbacks when ZeroClipboard is ready
    # these are set in ReactZeroClipboard::componentDidMount
    waitingForScriptToLoad.forEach (callback) ->
        callback()
        return
    return

result = (fnOrValue) ->
    if typeof fnOrValue == 'function'
        # call it if it's a function
        fnOrValue()
    else
        # just return it as is
        fnOrValue

handleZeroClipLoad(null)

# <ReactZeroClipboard 
#   text="text to copy"
#   html="<b>html to copy</b>"
#   richText="{\\rtf1\\ansi\n{\\b rich text to copy}}"
#   getText={(Void -> String)}
#   getHtml={(Void -> String)}
#   getRichText={(Void -> String)}
#
#   onCopy={(Event -> Void)}
#   onAfterCopy={(Event -> Void)}
#   onErrorCopy={(Error -> Void)}
#
#   onReady={(Event -> Void)}
# />
ReactZeroClipboard = React.createClass(

    ready: (cb) ->
        if client
            # nextTick guarentees asynchronus execution
            setTimeout cb.bind(this), 1
        else
            waitingForScriptToLoad.push cb.bind(this)
        return

    componentWillMount: ->
        if readyEventHasHappened and @props.onReady
            @props.onReady()
        return

    componentDidMount: ->
        # wait for ZeroClipboard to be ready, and then bind it to our element
        @eventRemovers = []
        @ready ->
            `var remover`
            if !@isMounted()
                return
            el = React.findDOMNode(this)
            client.clip el
            # translate our props to ZeroClipboard events, and add them to
            # our listeners
            for prop of @props
                eventName = propToEvent[prop]
                if eventName and typeof @props[prop] == 'function'
                    remover = addZeroListener(eventName, el, @props[prop])
                    @eventRemovers.push remover
            remover = addZeroListener('copy', el, @handleCopy)
            @eventRemovers.push remover
            return
        return

    componentWillUnmount: ->
        if client
            client.unclip @getDOMNode()
        # remove our event listener
        @eventRemovers.forEach (fn) ->
            fn()
            return
        return

    handleCopy: ->
        p = @props
        # grab or calculate the different data types
        text = result(p.getText or p.text)
        html = result(p.getHtml or p.html)
        richText = result(p.getRichText or p.richText)
        # give ourselves a fresh slate and then set
        # any provided data types
        client.clearData()
        richText != null and client.setRichText(richText)
        html != null and client.setHtml(html)
        text != null and client.setText(text)
        return
    render: ->
        React.Children.only @props.children
)

addZeroListener = (event, el, fn) ->
    eventHandlers[event].push [
        el
        fn
    ]
    ->
        handlers = eventHandlers[event]
        i = 0
        while i < handlers.length
            if handlers[i][0] == el
                # mutate the array to remove the listener
                handlers.splice i, 1
                return
            i++
        return


# #############################################
module.exports = ReactZeroClipboard

