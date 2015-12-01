# ---- begin references
formatters = require './formatters'
# ---- end references

# sharedSwapUtils functions


exports = {}

# #############################################
# local

formatCurrencyFn = formatters.formatCurrency

buildDetailsProse = {rate: {}, fixed: {}, fiat: {}, }

# ------------------------------------------------
buildDetailsProse.rate.sell = (swapConfig)->
    price = 1 / swapConfig.rate

    return "sells #{swapConfig.out} at a price of #{formatCurrencyFn(price)} #{swapConfig.in} each. A minimum sale of #{formatCurrencyFn(swapConfig.min)} #{swapConfig.in} is required."

buildDetailsProse.rate.buy = (swapConfig)->
    price = swapConfig.rate
    return "buys #{swapConfig.in} at a rate of #{formatCurrencyFn(price)} #{swapConfig.out} each."


# ------------------------------------------------
buildDetailsProse.fixed.sell = (swapConfig)->
    return "sells exactly #{formatCurrencyFn(swapConfig.out_qty)} #{swapConfig.out} for each exact multiple of #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in} received."

buildDetailsProse.fixed.buy = (swapConfig)->
    return "buys #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in} and pays #{formatCurrencyFn(swapConfig.out_qty)} #{swapConfig.out}.  Multiples of #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in} are accepted."


# ------------------------------------------------
buildDetailsProse.fiat.sell = (swapConfig)->
    formatFiatCurrency = formatters.formatArbitraryPrecisionFiatCurrency
    cost = swapConfig.cost
    
    if swapConfig.divisible
        divisibleMsg = "Partial units of #{swapConfig.out} may be sold."
    else
        divisibleMsg = "Only whole units of #{swapConfig.out} are sold. Extra BTC is returned as change."

    return "sells #{swapConfig.out} at a price of #{formatFiatCurrency(cost)} USD worth of #{swapConfig.in} each. A minimum sale of #{formatCurrencyFn(swapConfig.min_out)} #{swapConfig.out} is required. #{divisibleMsg}"

buildDetailsProse.fiat.buy = (swapConfig)->
    return ""


# #############################################
# exports

exports.swapDetailsProse = (swapConfig)->
    return buildDetailsProse[swapConfig.strategy][swapConfig.direction](swapConfig)



module.exports = exports

