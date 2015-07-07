# botutils functions
sbAdmin.botutils = do ()->
    botutils = {}

    settings = [
        {k: 'Swapbot Blue',   v: {start: 'rgba(0,29,62,0.95)',    end: 'rgba(8,85,135,0.95)'}}
        {k: 'Swapbot Green',  v: {start: 'rgba(32,142,78,0.95)',  end: 'rgba(46,204,113,0.95)'}}
        {k: 'Swapbot Yellow', v: {start: 'rgba(170,138,10,0.95)', end: 'rgba(241,196,15,0.95)'}}
        {k: 'Swapbot Red',    v: {start: 'rgba(191,39,24,0.95)',  end: 'rgba(231,76,6,0.95)'}}
    ]


    botutils.defaultOverlay = ()->
        return settings[0].v

    botutils.overlayOpts = () ->
        opts = []
        opts = [
            {k: '- No Overlay -', v: ''},
        ]
        for setting in settings
            opts.push({k: setting.k, v: window.JSON.stringify(setting.v)})

        return opts
    

    botutils.overlayDesc = (value)->
        for setting in settings
            if setting.v.start == value?.start and setting.v.end == value?.end
                return setting.k
        
        return 'No Overlay'


    return botutils
