# botUtils functions
swapbot = {} if not swapbot?

swapbot.botUtils = do ()->
    exports = {}

    # #############################################
    # local




    # #############################################
    # exports

    exports.formatConfirmations = (confirmations)->
        return 0 if not confirmations?
        return window.numeral(confirmations).format('0')

    exports.confirmationsProse = (bot)->
        return "#{bot.confirmationsRequired} #{exports.confirmationsWord(bot)}"
    
    exports.confirmationsWord = (bot)->
        return "confirmation#{if bot.confirmationsRequired == 1 then '' else 's'}"
    
    exports.getStatusFromBot = (bot)->
        return if bot.state == 'active' then 'active' else 'inactive'

    exports.newBotStatusFromEvent = (oldState, botEvent)->
        state = oldState
        event = botEvent.event
        switch event.name
            when 'bot.stateChange'
                if event.state == 'active'
                    state = 'active'
                else
                    state = 'inactive'
        return state



    return exports

