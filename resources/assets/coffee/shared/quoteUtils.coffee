# swapUtils functions
swapbot = {} if not swapbot?

swapbot.quoteUtils = do ()->
    exports = {}

    # #############################################
    # local




    # #############################################
    # exports

    exports.fiatQuoteSuffix = (swapConfig, amount, asset)->
        return '' if swapConfig.strategy != 'fiat'

        fiatAmount = QuotebotStore.getCurrentPrice() * amount
        return ' ('+swapbot.formatters.formatFiatCurrency(fiatAmount)+')'

    
    # #############################################
    return exports

