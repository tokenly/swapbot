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


    return utils
