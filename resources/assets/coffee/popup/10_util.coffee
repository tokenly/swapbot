util = do ()->
    exports = {}

    exports.sayHi = (text)->
        console.log "sayHi: #{text}"


    # #############################################
    return exports
