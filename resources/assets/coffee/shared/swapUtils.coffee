# swapUtils functions
swapbot = {} if not swapbot?

swapbot.swapUtils = do ()->
    exports = {}

    exports.SATOSHI = 100000000
    SATOSHI = exports.SATOSHI


    HARD_MINIMUM = 0.00000001

    # #############################################
    # local

    buildDesc = {}
    buildDesc.rate = (swapConfig)->
        outAmount = 1 * swapConfig.rate
        inAmount = 1

        formatCurrency = swapbot.formatters.formatCurrency

        # This bot will send you 1000 SOUP for every 1 BTC you deposit
        return "#{formatCurrency(outAmount)} #{swapConfig.out} for every #{formatCurrency(inAmount)} #{swapConfig.in} you deposit"

    buildDesc.fixed = (swapConfig)->
        formatCurrency = swapbot.formatters.formatCurrency
        return "#{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in} you deposit"

    buildDesc.fiat = (swapConfig)->
        formatCurrency = swapbot.formatters.formatCurrency
        outAmount = 1
        cost = swapConfig.cost
        return "#{formatCurrency(outAmount)} #{swapConfig.out} for every $#{formatCurrency(swapConfig.cost)} USD worth of #{swapConfig.in} you deposit"


    buildInAmountFromOutAmount = {}
    buildInAmountFromOutAmount.rate = (outAmount, swapConfig)->
        if not outAmount? or isNaN(outAmount)
            return 0

        # console.log "raw inAmount: ",outAmount / swapConfig.rate
        inAmount = Math.ceil(SATOSHI * outAmount / swapConfig.rate) / SATOSHI
        # console.log "rounded inAmount: ",inAmount

        return inAmount

    # this needs to be refined further
    buildInAmountFromOutAmount.fixed = (outAmount, swapConfig)->
        if not outAmount? or isNaN(outAmount)
            return 0

        inAmount = outAmount / (swapConfig.out_qty / swapConfig.in_qty)

        return inAmount

    buildInAmountFromOutAmount.fiat = (outAmount, swapConfig, currentRate)->
        if not outAmount? or isNaN(outAmount)
            return 0

        if currentRate == 0
            return 0

        cost = swapConfig.cost

        if swapConfig.divisible
            marketBuffer = 0
        else
            marketBuffer = 0.02

            # if marketBuffer is more 40% of the cost of a single token, then adjust it
            maxMarketBufferValue = cost * 0.40
            maxMarketBuffer = maxMarketBufferValue / outAmount
            if marketBuffer > maxMarketBuffer
                # console.log "maxMarketBuffer adjusted downwards from #{marketBuffer} to #{maxMarketBuffer}"
                marketBuffer = maxMarketBuffer

        inAmount = outAmount * cost / currentRate * (1 + marketBuffer)

        # console.log "currentRate=#{currentRate}.  inAmount=#{inAmount}"
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

    validateOutAmount.fiat = (outAmount, swapConfig)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig) 
        if errorMsg? then return errorMsg

        if swapConfig.min_out? and outAmount > 0 and outAmount < swapConfig.min_out
            formatCurrency = swapbot.formatters.formatCurrency
            return "To use this swap, you must purchase at least #{formatCurrency(swapConfig.min_out)} #{swapConfig.out}."

        return null

    validateInAmount = {}
    validateInAmount.shared = (inAmount, swapConfig)->
        if (""+inAmount).length == 0 then return null
        if isNaN(inAmount)
            return 'The amount to send does not look like a number.'
        if (inAmount) < HARD_MINIMUM
            return 'The amount to send is too small.'
        return null

    validateInAmount.rate = (inAmount, swapConfig)->
        errorMsg = validateInAmount.shared(inAmount, swapConfig) 
        if errorMsg? then return errorMsg

        if swapConfig.min? and inAmount < swapConfig.min
            formatCurrency = swapbot.formatters.formatCurrency
            return "This swap must be purchased by sending at least #{formatCurrency(swapConfig.min)} #{swapConfig.in}."

        return null

    validateInAmount.fixed = (inAmount, swapConfig)->
        errorMsg = validateInAmount.shared(inAmount, swapConfig) 
        if errorMsg? then return errorMsg

        # ratio = inAmount / swapConfig.out_qty
        # if ratio != Math.floor(ratio)
        #     formatCurrency = swapbot.formatters.formatCurrency
        #     return "This swap must be purchased at a rate of exactly #{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in}."

        return null

    validateInAmount.fiat = (inAmount, swapConfig)->
        errorMsg = validateInAmount.shared(inAmount, swapConfig) 
        if errorMsg? then return errorMsg
        return null


    # #############################################
    # exports


    # returns [firstSwapDescription, otherSwapDescriptions]
    exports.buildExchangeDescriptionsForGroup = (swapConfigGroup)->
        descs = []
        otherTokenTypes = []
        for swapConfig, index in swapConfigGroup
            descs.push(buildDesc[swapConfig.strategy](swapConfig))
            if index >= 1
                otherTokenTypes.push(swapConfig.in)
        if descs.length > 1
            otherCount = descs.length - 1
            # return [descs[0], "#{otherCount} other token#{if otherCount == 1 then '' else 's'} are also accepted"]
            if otherTokenTypes.length > 1
                otherTokenTypes[otherTokenTypes.length-1] = ' and '+otherTokenTypes[otherTokenTypes.length-1]
            if otherTokenTypes.length > 2
                tokenDescs = otherTokenTypes.join(', ')
            else
                tokenDescs = otherTokenTypes.join(' ')

            return [descs[0], "#{tokenDescs} #{if otherCount == 1 then 'is' else 'are'} also accepted"]
            # return [descs[0], "#{otherCount} other token#{if otherCount == 1 then '' else 's'} are also accepted"]
        return [descs[0], null]
        
    
    exports.inAmountFromOutAmount = (inAmount, swapConfig, currentRate)->
        inAmount = buildInAmountFromOutAmount[swapConfig.strategy](inAmount, swapConfig, currentRate)
        inAmount = 0 if inAmount == NaN
        return inAmount

    exports.validateOutAmount = (outAmount, swapConfig)->
        errorMsg = validateOutAmount[swapConfig.strategy](outAmount, swapConfig)
        if errorMsg? then return errorMsg

        # no error
        return null

    exports.validateInAmount = (inAmount, swapConfig)->
        errorMsg = validateInAmount[swapConfig.strategy](inAmount, swapConfig)
        if errorMsg? then return errorMsg

        # no error
        return null

    exports.groupSwapConfigs = (allSwapConfigs)->
        swapConfigGroupsByAssetOut = {}
        for swapConfig, index in allSwapConfigs
            if not swapConfigGroupsByAssetOut[swapConfig.out]?
                swapConfigGroupsByAssetOut[swapConfig.out] = []
            swapConfigGroupsByAssetOut[swapConfig.out].push(swapConfig)
        
        swapConfigGroups = []
        for k, v of swapConfigGroupsByAssetOut
            swapConfigGroups.push(v)
        return swapConfigGroups


    return exports

