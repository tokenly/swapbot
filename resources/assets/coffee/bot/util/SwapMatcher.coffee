SwapMatcher = do ()->
    exports = {}


    swapIsMatched = (swap, userChoices)->
        return true

    # #############################################

    exports.buildMatchedSwaps = (swaps, userChoices)->
        matchedSwaps = []
        for swap in swaps
            if swapIsMatched(swap, userChoices)
                matchedSwaps.push(swap)
        return matchedSwaps

    # #############################################
    return exports
