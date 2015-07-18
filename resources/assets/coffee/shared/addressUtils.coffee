# swapbot functions
swapbot = {} if not swapbot?

swapbot.addressUtils = do ()->
    exports = {}

    exports.publicBotHrefFromBot = (bot)->
        location = window.location
        return "#{location.protocol}//#{location.host}/public/#{bot.username}/#{bot.id}"
        

    exports.publicBotAddress = (username, botId, location)->
        # console.log "location",location
        return "#{location.protocol}//#{location.host}/public/#{username}/#{botId}"

    return exports
