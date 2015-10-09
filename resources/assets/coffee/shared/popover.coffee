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

        # launch body click handler
        $('body').off('click.sb-popover').on('click.sb-popover', (e)->
            if $(e.target).parents('.webui-popover').length then return
            el.webuiPopover('hide')
            return
        )

        # destroy body click handler
        el.on 'hide.webui.popover', (e)->
            $('body').off('click.sb-popover')
            return

        return

# ---------------------------------------------------------------------------------
module.exports = exports
