# botUtils functions
swapbot = {} if not swapbot?

swapbot.botUtils = do ()->
    exports = {}

    # #############################################
    # local




    # #############################################
    # exports

    exports.confirmationsProse = (bot)->
        return "#{bot.confirmationsRequired} #{exports.confirmationsWord(bot)}"
    
    exports.confirmationsWord = (bot)->
        return "confirmation#{if bot.confirmationsRequired == 1 then '' else 's'}"
    
    return exports

