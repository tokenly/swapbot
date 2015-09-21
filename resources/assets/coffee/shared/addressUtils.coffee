# swapbot functions


exports = {}

exports.publicBotHrefFromBot = (bot, location=null)->
    return exports.publicBotHref(bot.username, bot.urlSlug, bot.id, location)

exports.publicBotHrefFromSwap = (swap, location=null)->
    return exports.publicBotHref(swap.botUsername, swap.botUrlSlug, swap.botUuid, location)

exports.publicBotHref = (username, botUrlSlug, botId, location=null)->
    if botUrlSlug? and botUrlSlug.length > 0
        return "#{exports.publicBotHrefPrefix(location)}/#{username}/#{botUrlSlug}"

    return "#{exports.publicBotHrefPrefix(location)}/#{username}/#{botId}"

exports.publicBotHrefPrefix = (location=null)->
    location = window.location if not location?
    return "#{location.protocol}//#{location.host}/bot"



exports.publicSwapHref = (swap, botUsername=null, location=null)->
    location = window.location if not location?
    botUsername = swap.botUsername if not botUsername?
    return "#{location.protocol}//#{location.host}/swap/#{botUsername}/#{swap.id}"



module.exports = exports
