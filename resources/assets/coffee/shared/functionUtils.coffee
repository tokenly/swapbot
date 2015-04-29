# swapUtils functions
swapbot = {} if not swapbot?

swapbot.fnUtils = do ()->
    exports = {}

    callbacksQueue = {}
    callbackTimeouts = {}

    # #############################################
    # local




    # #############################################
    # exports

    # assumes that for each fn, there is a unique key
    # assumes that fn accepts one argument (a callback)
    exports.callOnceWithCallback = (key, fn, newCallback, timeout=5000)->
        if callbacksQueue[key]? and callbacksQueue[key].length > 0
            # just push on to the queue and wait for the first one to finish
            callbacksQueue[key].push(newCallback)
        else
            # new queue
            callbacksQueue[key] = []
            callbacksQueue[key].push(newCallback)

            # call the function
            runFunctionCall = ()->
                fn ()->
                    # function is complete - call all callbacks
                    for callback in callbacksQueue[key]
                        try
                            callback()
                        catch e
                            console.error(e)

                        # clear the queue
                        delete callbacksQueue[key]

                        # clear the timeout
                        clearTimeout(callbackTimeouts[key])
                        delete callbackTimeouts[key]


            # try again if timed out
            callbackTimeouts[key] = setTimeout ()->
                # this failed - try again
                runFunctionCall()
            , timeout

            # run the call once
            runFunctionCall()

                    
                    

                
                


    
    return exports

