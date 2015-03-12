# utils functions
sbAdmin.utils = do ()->
    utils = {}

    # clone an object
    utils.clone = (obj)->
        return obj if null == obj or "object" != typeof obj
        copy = obj.constructor()
        for attr of obj
            if obj.hasOwnProperty(attr) then copy[attr] = obj[attr]
        return copy

    utils.isEmpty = (obj) ->
        # null and undefined are "empty"
        return true  unless obj?
        
        # Assume if it has a length property with a non-zero value
        # that that property is correct.
        return false  if obj.length > 0
        return true  if obj.length is 0
        
        # Otherwise, does it have any properties of its own?
        # Note that this doesn't handle
        # toString and valueOf enumeration bugs in IE < 9
        for key of obj
            return false  if hasOwnProperty.call(obj, key)
        true

    # returns an array of colum lengths
    #   example: utils.splitColumns(3, 11) returns [3,4,4]
    utils.splitColumns = (elementsCount, totalColumns)->
        baseColSize = Math.floor(totalColumns / elementsCount)
        remainder = (totalColumns % elementsCount)

        cumRemainder = 0
        totalColsUsed = 0

        cols = []
        for i in [0...elementsCount]
            isLast = (i == elementsCount-1)
            if isLast
                colSize = totalColumns - totalColsUsed
            else
                colSize = baseColSize

                cumRemainder += remainder
                if cumRemainder >= elementsCount
                    cumRemainder -= elementsCount
                    ++colSize

                totalColsUsed += colSize

            cols.push(colSize)

        return cols


    utils.splitColumnsWithOverrides = (elementsCount, totalColumns, overrides)->
        # build the overrides
        #   and use -1 if not defined
        overrideCols = []
        elsToSplit = elementsCount
        colsToSplit = totalColumns
        for i in [0...elementsCount]
            if overrides?[i]
                overrideCols.push(overrides[i])
                colsToSplit -= overrides[i]
                elsToSplit -= 1
            else
                overrideCols.push(-1)

        # split the remaining columns
        splitColumns = utils.splitColumns(elsToSplit, colsToSplit)
        
        # merge the overrides and splits
        cols = []
        nextSplitColumnOffset = 0
        for overrideCol in overrideCols
            if overrideCol == -1
                cols.push(splitColumns[nextSplitColumnOffset])
                ++nextSplitColumnOffset
            else
                cols.push(overrideCol)
        return cols




    return utils

window.utils = sbAdmin.utils