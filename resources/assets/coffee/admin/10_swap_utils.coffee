# swaputils functions
sbAdmin.swaputils = do ()->
    swaputils = {}

    # clone an object
    swaputils.newSwapProp = (swap={})->
        # all possible properties
        # console.log "divisible: "+(if swap.divisible? then (if swap.divisible then '1' else '0') else '0')
        return m.prop({
            strategy : m.prop(swap.strategy  or 'rate')
            in       : m.prop(swap.in        or '')
            out      : m.prop(swap.out       or '')
            rate     : m.prop(swap.rate      or '')
            in_qty   : m.prop(swap.in_qty    or '')
            out_qty  : m.prop(swap.out_qty   or '')
            min      : m.prop(swap.min       or '')

            cost     : m.prop(swap.cost      or '')
            min_out  : m.prop(swap.min_out   or '')
            divisible: m.prop(if swap.divisible? then (if swap.divisible then '1' else '0') else '0')
        })

    swaputils.allStrategyOptions = ()->
        return [
            {k: "By Rate",          v: 'rate'}
            {k: "By Fixed Amounts", v: 'fixed'}
            {k: "By USD Amount paid in BTC",    v: 'fiat'}
        ]

    strategyLabelCache = null
    swaputils.strategyLabelByValue = (strategyValue)->
        if strategyLabelCache == null
            strategyLabelCache = {}
            swaputils.allStrategyOptions().map (opt)->
                strategyLabelCache[opt.v] = opt.k
                return

        return strategyLabelCache[strategyValue]

    return swaputils

