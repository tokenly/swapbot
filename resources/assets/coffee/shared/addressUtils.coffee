# swapbot functions
swapbot = {} if not swapbot?

swapbot.addressUtils = do ()->
    exports = {}

    exports.publicBotAddress = (username, botId, location)->
        # console.log "location",location
        return "#{location.protocol}//#{location.host}/public/#{username}/#{botId}"

    exports.poupupBotAddress = (username, botId, location)->
        # console.log "location",location
        return exports.publicBotAddress(username, botId, location)+"/popup"

    return exports
