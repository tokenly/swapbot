# swapUtils functions

swapbot = swapbot or {}; swapbot.formatters = require './formatters'

exports = {}

exports.SATOSHI = 100000000
SATOSHI = exports.SATOSHI


HARD_MINIMUM = 0.00000001

# #############################################
# local

formatCurrencyFn = swapbot.formatters.formatCurrency


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
# local

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
    formatFiatCurrency = swapbot.formatters.formatArbitraryPrecisionFiatCurrency
    cost = swapConfig.cost
    
    if swapConfig.divisible
        divisibleMsg = "Partial units of #{swapConfig.out} may be sold."
    else
        divisibleMsg = "Only whole units of #{swapConfig.out} are sold. Extra BTC is returned as change."

    return "sells #{swapConfig.out} at a price of #{formatFiatCurrency(cost)} USD worth of #{swapConfig.in} each. A minimum sale of #{formatCurrencyFn(swapConfig.min_out)} #{swapConfig.out} is required. #{divisibleMsg}"

buildDetailsProse.fiat.buy = (swapConfig)->
    return ""


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

buildOutAmountFromInAmount = {}
buildOutAmountFromInAmount.rate = (inAmount, swapConfig)->
    if not inAmount? or isNaN(inAmount)
        return 0

    outAmount = Math.floor(swapConfig.rate * inAmount * SATOSHI) / SATOSHI

    return outAmount

buildOutAmountFromInAmount.fixed = (inAmount, swapConfig)->
    if not inAmount? or isNaN(inAmount)
        return 0

    multipler = Math.floor(Math.round(inAmount * SATOSHI) / (swapConfig.in_qty * SATOSHI))
    outAmount = multipler * swapConfig.out_qty

    return outAmount

buildOutAmountFromInAmount.fiat = (inAmount, swapConfig, currentRate)->
    # not implemented
    return 0




# #############################################

validateOutAmount = {}
validateOutAmount.shared = (outAmount, swapConfig, botBalance)->
    if (""+outAmount).length == 0 then return null
    if isNaN(outAmount)
        return 'The amount to purchase does not look like a number.'

    if not botBalance? or outAmount > botBalance
        return "There is not enough #{swapConfig.out} in stock to complete this swap."

    return null

validateOutAmount.rate = (outAmount, swapConfig, botBalance)->
    errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance) 
    if errorMsg? then return errorMsg
    return null

validateOutAmount.fixed = (outAmount, swapConfig, botBalance)->
    errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance) 
    if errorMsg? then return errorMsg

    formatCurrencyFn = swapbot.formatters.formatCurrency

    ratio = outAmount / swapConfig.out_qty
    if ratio != Math.floor(ratio)
        return "This swap must be purchased at a rate of exactly #{formatCurrencyFn(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in}."

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

    formatCurrencyFn = swapbot.formatters.formatCurrency

    inAmountSatoshis = Math.round(inAmount * SATOSHI)
    inQtySatoshis = Math.round(swapConfig.in_qty * SATOSHI)
    if inAmountSatoshis < inQtySatoshis
        return "You must send at least #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in} to use this swap."


    ratio = inAmountSatoshis / inQtySatoshis
    if ratio != Math.floor(ratio)
        return "You must sell a multiple of #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in}. You will receive #{formatCurrencyFn(swapConfig.out_qty)} #{swapConfig.out} for every #{formatCurrencyFn(swapConfig.in_qty)} #{swapConfig.in}."

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
        fiatSuffix = ' ('+swapbot.formatters.formatFiatCurrency(buffer * currentRate)+')'
        return React.createElement('span',{className: "changeMessage"}, [
            "This includes a ",
            React.createElement('span', {className: "popover", title: "More about buffering", onClick: showChangeMessagePopover}, "buffer"),
            " of #{swapbot.formatters.formatCurrency(buffer)} #{assetIn} #{fiatSuffix}.",
        ])
            


    return null

# #############################################
# exports


exports.swapDetailsProse = (swapConfig)->
    return buildDetailsProse[swapConfig.strategy][swapConfig.direction](swapConfig)


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

exports.outAmountFromInAmount = (inAmount, swapConfig)->
    outAmount = buildOutAmountFromInAmount[swapConfig.strategy](inAmount, swapConfig)
    outAmount = 0 if outAmount == NaN
    return outAmount

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

# groups by asset out and 
exports.groupSwapConfigs = (allSwapConfigs)->
    groups = {
        sell: []
        buy: []
    }

    sellingConfigsByAssetOut = {}
    buyingConfigsByAssetIn = {}

    for swapConfig, index in allSwapConfigs
        if swapConfig.direction == 'sell'
            if not sellingConfigsByAssetOut[swapConfig.out]? then sellingConfigsByAssetOut[swapConfig.out] = []
            sellingConfigsByAssetOut[swapConfig.out].push(swapConfig)

        else if swapConfig.direction == 'buy'
            if not buyingConfigsByAssetIn[swapConfig.in]? then buyingConfigsByAssetIn[swapConfig.in] = []
            buyingConfigsByAssetIn[swapConfig.in].push(swapConfig)


    for _k, configs of sellingConfigsByAssetOut
        groups.sell.push(configs)
    for _k, configs of buyingConfigsByAssetIn
        groups.buy.push(configs)

    return groups

exports.getBuySwapConfigsByInAsset = (allSwapConfigs, inAsset)->
    swapConfigsOut = []
    for swapConfig, index in allSwapConfigs
        if swapConfig.direction == 'buy' and swapConfig.in == inAsset
            swapConfigsOut.push(swapConfig)

    return swapConfigsOut


exports.calculateMaxBuyableAmount = (botBalances, swapConfigs)->
    maxBuyableAmount = 0
    for swapConfig in swapConfigs
        inAmount = exports.inAmountFromOutAmount(botBalances[swapConfig.out], swapConfig, null)
        maxBuyableAmount = inAmount if inAmount > maxBuyableAmount

    return maxBuyableAmount



module.exports = exports

