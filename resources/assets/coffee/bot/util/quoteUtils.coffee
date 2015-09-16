# quoteUtils functions

QuotebotStore = require '../stores/QuotebotStore'
swapbot = swapbot or {}; swapbot.formatters = require '../../shared/formatters'


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
module.exports = exports

