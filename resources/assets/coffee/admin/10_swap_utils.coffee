# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.constants = require './05_constants'
swapbot = swapbot or {}; swapbot.formatters = require '../shared/formatters'
# ---- end references

# swaputils functions
swaputils = {}

constants = sbAdmin.constants

# clone an object
swaputils.newSwapProp = (swap={}, swapRulesProp=null)->
    # calculate price (1 / rate)
    price = null

    if swap.direction == constants.DIRECTION_SELL and swap.price? and ((typeof swap.price == 'string' and swap.price.length > 0) or (typeof swap.price == 'number' and swap.price > 0))
        price = swapbot.formatters.formatCurrencyAsNumber(swap.price)

    else if swap.rate? and ((typeof swap.rate == 'string' and swap.rate.length > 0) or (typeof swap.rate == 'number' and swap.rate > 0))
        price = swapbot.formatters.formatCurrencyAsNumber(1 / swap.rate)

    # build swapRules from swap_rule_ids
    swapRules = []
    if swapRulesProp? and swap.swap_rule_ids?
        swapRules = swapRulesProp().filter (swapRule)->
            for swap_rule_id in swap.swap_rule_ids
                if swapRule.uuid() == swap_rule_id then return true
            return false

    # all possible properties
    return m.prop({
        strategy : m.prop(swap.strategy  or 'rate')
        in       : m.prop(swap.in        or '')
        out      : m.prop(swap.out       or '')
        rate     : m.prop(swap.rate      or '')
        price    : m.prop(price          or '')
        in_qty   : m.prop(swap.in_qty    or '')
        out_qty  : m.prop(swap.out_qty   or '')
        min      : m.prop(swap.min       or '')

        cost     : m.prop(swap.cost      or '')
        min_out  : m.prop(swap.min_out   or '')
        divisible: m.prop(if swap.divisible? then (if swap.divisible then '1' else '0') else '0')

        direction: m.prop(swap.direction or 'sell')

        swapRules: m.prop(swapRules)
    })

swaputils.normalizeSwapsForSaving = (swaps)->
    # console.log "swaps in: ",swaps
    swapsOut = swaps.map (swap)->
        # console.log "normalizeSwapsForSaving swap.offset()=", (if swap.offset then swap.offset() else null)
        # console.log "normalizeSwapsForSaving swap.swapRules()=", (if swap.swapRules then swap.swapRules() else null)
        # convert price back to rate for each sell swap
        if swap.direction() == constants.DIRECTION_SELL
            rate = ''
            price = swap.price()
            if price? and price.length > 0
                # rate = swapbot.formatters.formatCurrencyAsNumber(1 / price)
                rate = 1 / price

            swap.rate(rate)

        # clone and serialize
        swapOut = JSON.parse(JSON.stringify(swap))

        # extract just the ids from the swapRules
        if swapOut.swapRules?
            swapOut.swap_rule_ids = swapOut.swapRules.map (swapRule)->
                return swapRule.uuid
        delete swapOut.swapRules

        return swapOut

    # console.log "swapsOut=",swapsOut
    return swapsOut

swaputils.buildSwapsPropValue = (swaps, swapRulesProp, defaultSwapDirection=constants.DIRECTION_SELL)->
    out = []
    for swap in swaps
        out.push(swaputils.newSwapProp(swap, swapRulesProp))

    # always have at least one
    if not out.length and defaultSwapDirection == constants.DIRECTION_SELL
        out.push(swaputils.newSwapProp({direction: defaultSwapDirection}))

    return out


swaputils.allStrategyOptions = (swapDirection)->
    if swapDirection == constants.DIRECTION_BUY
        return [
            {k: "By Price",                  v: 'rate'}
            {k: "By Fixed Amounts",          v: 'fixed'}
        ]

    else
        return [
            {k: "By Price",                  v: 'rate'}
            {k: "By Fixed Amounts",          v: 'fixed'}
            {k: "By USD Amount paid in BTC", v: 'fiat'}
        ]

strategyLabelCache = null
swaputils.strategyLabelByValue = (strategyValue)->
    if strategyLabelCache == null
        strategyLabelCache = {}
        swaputils.allStrategyOptions().map (opt)->
            strategyLabelCache[opt.v] = opt.k
            return

    return strategyLabelCache[strategyValue]

module.exports = swaputils

