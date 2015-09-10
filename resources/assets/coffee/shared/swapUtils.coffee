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
        [normalizedOutAmount, normalizedInAmount] = normalizeInAndOutQuantities(outAmount, inAmount)
        return "#{formatCurrency(normalizedOutAmount)} #{swapConfig.out} for every #{formatCurrency(normalizedInAmount)} #{swapConfig.in} you deposit"

    buildDesc.fixed = (swapConfig)->
        formatCurrency = swapbot.formatters.formatCurrency
        return "#{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in} you deposit"

    buildDesc.fiat = (swapConfig)->
        formatCurrency = swapbot.formatters.formatCurrency
        formatFiatCurrency = swapbot.formatters.formatArbitraryPrecisionFiatCurrency
        outAmount = 1
        cost = swapConfig.cost
        [normalizedOutAmount, normalizedInAmount] = normalizeInAndOutQuantities(outAmount, swapConfig.cost)
        return "#{formatCurrency(normalizedOutAmount)} #{swapConfig.out} for every #{formatFiatCurrency(normalizedInAmount)} USD worth of #{swapConfig.in} you deposit"


    normalizeInAndOutQuantities = (rawOut, rawIn, minValue=1)->
        if rawOut < minValue and rawOut > 0
            multiplier = minValue / rawOut
            normalizedOut = rawOut * multiplier
            normalizedIn = rawIn * multiplier
        else if rawIn < minValue and rawIn > 0
            multiplier = minValue / rawIn
            normalizedIn = rawIn * multiplier
            normalizedOut = rawOut * multiplier
        else
            normalizedOut = rawOut
            normalizedIn = rawIn

        return [normalizedOut, normalizedIn]

    # #############################################

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

        [inAmount, buffer] = buildInAmountAndBuffer(outAmount, swapConfig, currentRate)
        return inAmount + buffer


    buildInAmountAndBuffer = (outAmount, swapConfig, currentRate)->
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
                marketBuffer = maxMarketBuffer

        inAmount = outAmount * cost / currentRate
        buffer = inAmount * marketBuffer

        return [inAmount, buffer]


    # #############################################

    validateOutAmount = {}
    validateOutAmount.shared = (outAmount, swapConfig, botBalance)->
        if (""+outAmount).length == 0 then return null
        if isNaN(outAmount)
            return 'The amount to purchase does not look like a number.'

        if not botBalance? or outAmount > botBalance
            return "There is not enough #{swapConfig.out} available to make this purchase."

        return null

    validateOutAmount.rate = (outAmount, swapConfig, botBalance)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance) 
        if errorMsg? then return errorMsg
        return null

    validateOutAmount.fixed = (outAmount, swapConfig, botBalance)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance) 
        if errorMsg? then return errorMsg

        ratio = outAmount / swapConfig.out_qty
        if ratio != Math.floor(ratio)
            formatCurrency = swapbot.formatters.formatCurrency
            return "This swap must be purchased at a rate of exactly #{formatCurrency(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrency(swapConfig.in_qty)} #{swapConfig.in}."

        return null

    validateOutAmount.fiat = (outAmount, swapConfig, botBalance)->
        errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance) 
        if errorMsg? then return errorMsg

        if swapConfig.min_out? and outAmount > 0 and outAmount < swapConfig.min_out
            formatCurrency = swapbot.formatters.formatCurrency
            return "To use this swap, you must purchase at least #{formatCurrency(swapConfig.min_out)} #{swapConfig.out}."

        return null

    # #############################################

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

    showChangeMessagePopover = (e)->
        e.preventDefault()
        e.stopPropagation()

        content = """
            <p>The tokens you are purchasing have a price set in dollars.  Since the price of bitcoin constantly changes, please deposit this additional buffer to make sure you send enough to complete your purchase.</p>
            <p>Your price in BTC is locked in the as soon as the bot sees your transaction on the bitcoin network.  Any excess is refunded and you may be refunded more than the buffer if the price of BTC goes up or less than the buffer if the BTC price goes down.</p>
        """

        el = $(e.target)
        # console.log "clicked: ",el
        el.webuiPopover({trigger:'manual', title:'About the BTC Buffer', content: content, animation:'pop', closeable: true, })
        el.webuiPopover('show')

        return

    buildChangeMessage = {}
    buildChangeMessage.fiat = (outAmount, swapConfig, currentRate)->
        [inAmount, buffer] = buildInAmountAndBuffer(outAmount, swapConfig, currentRate)
        if buffer? and Math.round(buffer * exports.SATOSHI) > 0
            assetIn = swapConfig.in
            return React.createElement('span',{className: "changeMessage"}, [
                "This includes a ",
                React.createElement('span', {className: "popover", title: "More about buffering", onClick: showChangeMessagePopover}, "buffer"),
                " of #{swapbot.formatters.formatCurrency(buffer)} #{assetIn} #{swapbot.quoteUtils.fiatQuoteSuffix(swapConfig, buffer, assetIn)}.",
            ])
                


        return null

    # #############################################
    # exports


    # returns [firstSwapDescription, otherSwapDescriptions]
    exports.buildExchangeDescriptionsForGroup = (swapConfigGroup)->
        mainDesc = ''
            
        otherTokenEls = []
        for swapConfig, index in swapConfigGroup
            if index == 0
                mainDesc = buildDesc[swapConfig.strategy](swapConfig)
            if index >= 1
                otherTokenEls.push(React.createElement('span',{key: 'token'+index, className: 'tokenType'}, swapConfig.in))

        if otherTokenEls.length == 0
            return [mainDesc, otherSwapDescriptions]

        tokenDescs = []
        otherCount = otherTokenEls.length
        if otherCount == 1
            otherSwapDescriptions = React.createElement('span', null, [otherTokenEls[0], ' is also accepted'])
        else if otherCount == 2
            # X and Y
            otherSwapDescriptions = React.createElement('span', null, [otherTokenEls[0], ' and ', otherTokenEls[1], ' are also accepted'])
        if otherCount > 2
            # X, Y and Z
            els = []
            for otherTokenEl, index in otherTokenEls
                if index == otherTokenEls.length - 1
                    els.push(' and ')
                    els.push(otherTokenEl)
                else if index >= 1
                    els.push(', ')
                    els.push(otherTokenEl)
                else 
                    els.push(otherTokenEl)
            
            otherSwapDescriptions = React.createElement('span', null, [els, ' are also accepted'])

        return [mainDesc, otherSwapDescriptions]
        
    
    exports.inAmountFromOutAmount = (outAmount, swapConfig, currentRate)->
        inAmount = buildInAmountFromOutAmount[swapConfig.strategy](outAmount, swapConfig, currentRate)
        inAmount = 0 if inAmount == NaN
        return inAmount

    exports.validateOutAmount = (outAmount, swapConfig, botBalance)->
        errorMsg = validateOutAmount[swapConfig.strategy](outAmount, swapConfig, botBalance)
        if errorMsg? then return errorMsg

        # no error
        return null

    exports.validateInAmount = (inAmount, swapConfig)->
        errorMsg = validateInAmount[swapConfig.strategy](inAmount, swapConfig)
        if errorMsg? then return errorMsg

        # no error
        return null

    exports.buildChangeMessage = (outAmount, swapConfig, currentRate)->
        return buildChangeMessage[swapConfig.strategy]?(outAmount, swapConfig, currentRate)

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

