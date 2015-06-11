SwapMatcher = do ()->
    exports = {}


    swapIsMatched = (swap, userChoices)->
        if swap.assetIn = userChoices.inAsset and swapbot.formatters.formatCurrency(swap.quantityIn) == swapbot.formatters.formatCurrency(userChoices.inAmount)
            return true
        return false

    # #############################################

    exports.buildMatchedSwaps = (swaps, userChoices)->
        matchedSwaps = []
        for swap in swaps
            if swapIsMatched(swap, userChoices)
                matchedSwaps.push(swap)
        return matchedSwaps

    # #############################################
    return exports
