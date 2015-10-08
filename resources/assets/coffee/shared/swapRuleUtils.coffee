# swapRuleUtils functions

formatters   = require './formatters'
BotConstants = require '../bot/constants/BotConstants'

exports = {}

# #############################################
# local

getAllSwapRulesFromConfigs = (swapConfigs)->
    allSwapRules = []
    swapConfigs.map (swapConfig)->
        if swapConfig.swapRules?
            swapConfig.swapRules.map (swapRule)-> allSwapRules.push(swapRule)
        return
    return allSwapRules

findBestDiscount = (allSwapRules, orderQuantity=null)->
    if not allSwapRules? then return null

    bestPct = null
    bestDiscount = null
    allSwapRules.map (swapRule)->
        if swapRule.ruleType == BotConstants.RULE_TYPE_BULK_DISCOUNT
            swapRule.discounts.map (discount)->
                if orderQuantity? and discount.moq > orderQuantity
                    return

                if bestPct == null or discount.pct > bestPct
                    bestPct = discount.pct
                    bestDiscount = discount
                return
        return
    return bestDiscount

sortDiscounts = (discounts)->
    sortedDiscounts = discounts.sort (a, b)->
        return a.pct - b.pct
    return sortedDiscounts

# #############################################
# exports


exports.buildSwapRuleGroupSummaryProse = (swapConfigs)->
    allSwapRules = getAllSwapRulesFromConfigs(swapConfigs)
    bestDiscount = findBestDiscount(allSwapRules)
    if bestDiscount
        return "Offers bulk discounts up to #{formatters.formatPercentage(bestDiscount.pct * 100, 1)}%"

    return null

exports.buildDiscountMessageTextForPlaceOrder = (swapConfig)->
    if not swapConfig.swapRules then return null

    # combine discounts
    combinedDiscounts = []
    swapConfig.swapRules.map (swapRule)->
        if swapRule.ruleType == BotConstants.RULE_TYPE_BULK_DISCOUNT
            swapRule.discounts.map (discount)-> combinedDiscounts.push(discount)
    if not combinedDiscounts.length then return null

    # build discount texts
    discountTexts = []
    sortDiscounts(combinedDiscounts).map (discount)->
        discountTexts.push("a #{formatters.formatPercentage(discount.pct * 100)}% discount for purchasing #{discount.moq} #{swapConfig.out}")
    dtLength = discountTexts.length
    if not dtLength then return null

    if dtLength > 1
        discountTexts[dtLength-1] = "and #{discountTexts[dtLength-1]}"


    return "Offers "+discountTexts.join(", ")+"."

exports.modifyInitialQuantityIn = (outAmount, inAmount, swapConfig)->
    bestDiscount = findBestDiscount(swapConfig.swapRules, outAmount)
    if bestDiscount?
        return inAmount * (1 - bestDiscount.pct)

    return null

exports.getAppliedDiscount = (outAmount, swapConfig)->
    bestDiscount = findBestDiscount(swapConfig.swapRules, outAmount)
    return bestDiscount



module.exports = exports

