invariant = (condition, format, a, b, c, d, e, f) ->
    if __DEV__?
        if format == undefined
            throw new Error('invariant requires an error message argument')
    if !condition
        error = undefined
        if format == undefined
            error = new Error('Minified exception occurred; use the non-minified dev environment ' + 'for the full error message and additional helpful warnings.')
        else
            args = [
                a
                b
                c
                d
                e
                f
            ]
            argIndex = 0
            error = new Error('Invariant Violation: ' + format.replace(/%s/g, ->
                args[argIndex++]
            ))
        error.framesToPop = 1
        # we don't care about invariant's own frame
        throw error
    return

