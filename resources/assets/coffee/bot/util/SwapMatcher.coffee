SwapMatcher = do ()->
    exports = {}


    swapIsMatched = (swap, userChoices)->
        # never match completed swaps
        if swap.isComplete
            return false

        # ignore old swaps
        if userChoices.swapIDsToIgnore[swap.id]?
            return false

        # always match when mode is MATCH_SHOW_ALL
        if userChoices.swapMatchMode == UserChoiceStore.MATCH_SHOW_ALL
            return true

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
