# formatters functions
swapbot = {} if not swapbot?

swapbot.formatters = do ()->
    exports = {}

    # #############################################
    # local

    SATOSHI = 100000000

    # #############################################
    # exports

    exports.formatConfirmations = (confirmations)->
        return 0 if not confirmations?
        return window.numeral(confirmations).format('0')

    exports.confirmationsProse = (bot)->
        return "#{bot.confirmationsRequired} #{exports.confirmationsWord(bot)}"
    
    exports.confirmationsWord = (bot)->
        return "confirmation#{if bot.confirmationsRequired == 1 then '' else 's'}"
    
    exports.satoshisToValue = (amount, currencyPostfix='BTC') ->
        return exports.formatCurrency(amount / SATOSHI, currencyPostfix)

    exports.formatCurrency = (value, currencyPostfix='') ->
        return window.numeral(value).format('0,0.[00000000]') + (if currencyPostfix?.length then ' '+currencyPostfix else '')

    return exports

