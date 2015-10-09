# popover functions

exports = {}


exports.buildOnClick = (popoverConfig)->
    return (e)->
        e.preventDefault()
        e.stopPropagation()

        popoverConfig.trigger = 'manual'
        if not popoverConfig.animation? then popoverConfig.animation = 'pop'
        if not popoverConfig.closeable? then popoverConfig.closeable = true
        if not popoverConfig.width? then popoverConfig.width = 420

        el = jQuery(e.target)
        el.webuiPopover(popoverConfig)
        el.webuiPopover('show')
        return

# ---------------------------------------------------------------------------------
module.exports = exports
