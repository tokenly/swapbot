# botutils functions
sbAdmin.botutils = do ()->
    botutils = {}

    botutils.overlayOpts = () ->
        opts = []
        opts = [
            {k: '- No Overlay -', v: ''},
            {k: 'Swapbot Blue', v: 'gradient.png'},
        ]

        return opts
    

    botutils.overlayDesc = (value)->
        switch value
            when 'gradient.png'
                return 'Swapbot Blue'
        return 'No Overlay'

    return botutils
