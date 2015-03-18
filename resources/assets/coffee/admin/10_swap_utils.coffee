# swaputils functions
sbAdmin.swaputils = do ()->
    swaputils = {}

    # clone an object
    swaputils.newSwapProp = (swap={})->
        # all possible properties
        return m.prop({
            strategy: m.prop(swap.strategy or 'rate')
            in      : m.prop(swap.in       or '')
            out     : m.prop(swap.out      or '')
            rate    : m.prop(swap.rate     or '')
            in_qty  : m.prop(swap.in_qty   or '')
            out_qty : m.prop(swap.out_qty  or '')
            min     : m.prop(swap.min      or '')
        })

    swaputils.allStrategyOptions = ()->
        return [
            {k: "By Rate",          v: 'rate'}
            {k: "By Fixed Amounts", v: 'fixed'}
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
