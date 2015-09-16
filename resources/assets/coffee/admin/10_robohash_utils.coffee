robohashURLBase = null

# robohashUtils functions
robohashUtils = {}



getRobohashURLBase = ()->
    if not robohashURLBase?
        robohashURLBase = window.ROBOHASH_URL
        
    return robohashURLBase

# ------------------------------------------------------------

robohashUtils.img = (hash, className=null)->
    if not hash
        return null

    attrs = {src: robohashUtils.robohashURL(hash)}
    if className?
        attrs.class = className

    return m("img", attrs)

robohashUtils.robohashURL = (hash)->
    if not hash
        return null

    return "#{getRobohashURLBase()}/#{hash}.png?set=set3"

module.exports = robohashUtils
