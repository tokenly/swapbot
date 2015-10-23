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

        # launch body click handler
        $('body').off('click.sb-popover').on('click.sb-popover', (e)->
            if $(e.target).parents('.webui-popover').length then return
            el.webuiPopover('hide')
            return
        )

        # destroy body click handler
        el.off('hide.webui.popover').on 'hide.webui.popover', (e)->
            $('body').off('click.sb-popover')
            return


        el.off('shown.webui.popover').on 'shown.webui.popover', (e)->
            if popoverConfig.onShown?
                popoverConfig.onShown.call(this, e)
            return

        el.webuiPopover(popoverConfig)
        el.webuiPopover('show')


        return

# ---------------------------------------------------------------------------------
module.exports = exports
