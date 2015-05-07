# swapUtils functions
swapbot = {} if not swapbot?

swapbot.swapUtils = do ()->
    exports = {}

    # #############################################
    # local

    buildDesc = {}
    buildDesc.rate = (swapConfig)->
        outAmount = 1 * swapConfig.rate
        inAmount = 1

        formatCurrency = swapbot.formatters.formatCurrency

        # This bot will send you 1000 SOUP for every 1 BTC you deposit
        return "This bot will send you #{formatCurrency(outAmount)} #{swapConfig.out} for every #{formatCurrency(inAmount)} #{swapConfig.in} you deposit."

    buildDesc.fixed = (swapConfig)->
        formatCurrency = swapbot.formatters.formatCurrency
        # return "#{swapConfig.out_qty} #{swapConfig.out} for #{swapConfig.in_qty} #{swapConfig.in}"
        return "This bot will send you #{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in} you deposit."


    buildInAmountFromOutAmount = {}
    buildInAmountFromOutAmount.rate = (outAmount, swapConfig)->
        if not outAmount? or isNaN(outAmount)
            return 0

        inAmount = outAmount / swapConfig.rate
        return inAmount

    # this needs to be refined further
    buildInAmountFromOutAmount.fixed = (outAmount, swapConfig)->
        if not outAmount? or isNaN(outAmount)
            return 0

        inAmount = outAmount / (swapConfig.out_qty / swapConfig.in_qty)

        return inAmount


    validateOutAmount = {}
    validateOutAmount.shared = (outAmount, swapConfig)->
        if (""+outAmount).length == 0 then return null
        if isNaN(outAmount)
            return 'The amount to purchase does not look like a number.'
        return null

    validateOutAmount.rate = (outAmount, swapConfig)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig) 
        if errorMsg? then return errorMsg
        return null

    validateOutAmount.fixed = (outAmount, swapConfig)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig) 
        if errorMsg? then return errorMsg

        ratio = outAmount / swapConfig.out_qty
        if ratio != Math.floor(ratio)
            formatCurrency = swapbot.formatters.formatCurrency
            return "This swap must be purchased at a rate of exactly #{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in}."

        return null



    # #############################################
    # exports

    exports.exchangeDescription = (swapConfig)->
        return buildDesc[swapConfig.strategy](swapConfig)
    
    exports.inAmountFromOutAmount = (inAmount, swapConfig)->
        inAmount = buildInAmountFromOutAmount[swapConfig.strategy](inAmount, swapConfig)
        inAmount = 0 if inAmount == NaN
        return inAmount

    exports.validateOutAmount = (outAmount, swapConfig)->
        errorMsg = validateOutAmount[swapConfig.strategy](outAmount, swapConfig)
        if errorMsg? then return errorMsg

        # no error
        return null

    return exports

