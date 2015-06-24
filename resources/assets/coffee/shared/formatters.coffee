# formatters functions
swapbot = {} if not swapbot?

swapbot.formatters = do ()->
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
    
    exports.satoshisToValue = (amount, currencyPostfix='BTC') ->
        SATOSHI = swapbot.swapUtils.SATOSHI
        return exports.formatCurrency(amount / SATOSHI, currencyPostfix)

    exports.formatCurrency = (value, currencyPostfix='') ->
        if not value? or isNaN(value) then return ''
        return window.numeral(value).format('0,0.[00000000]') + (if currencyPostfix?.length then ' '+currencyPostfix else '')

    return exports

