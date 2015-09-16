# botutils functions
botutils = {}

settings = [
    {isGroup: true, label: 'Swapbot Blue', opts: [
        {k: 'Blue (Light Tint)',    v: {start: 'rgba(0,29,62,0.30)',    end: 'rgba(8,85,135,0.30)'}}
        {k: 'Blue (Medium Tint)',   v: {start: 'rgba(0,29,62,0.60)',    end: 'rgba(8,85,135,0.60)'}}
        {k: 'Blue (Heavy Tint)',    v: {start: 'rgba(0,29,62,0.90)',    end: 'rgba(8,85,135,0.90)'}}
    ]},
    {isGroup: true, label: 'Swapbot Green', opts: [
        {k: 'Green (Light Tint)',   v: {start: 'rgba(32,142,78,0.30)',  end: 'rgba(46,204,113,0.30)'}}
        {k: 'Green (Medium Tint)',  v: {start: 'rgba(32,142,78,0.60)',  end: 'rgba(46,204,113,0.60)'}}
        {k: 'Green (Heavy Tint)',   v: {start: 'rgba(32,142,78,0.90)',  end: 'rgba(46,204,113,0.90)'}}
    ]},
    {isGroup: true, label: 'Swapbot Yellow', opts: [
        {k: 'Yellow (Light Tint)',  v: {start: 'rgba(170,138,10,0.30)', end: 'rgba(241,196,15,0.30)'}}
        {k: 'Yellow (Medium Tint)', v: {start: 'rgba(170,138,10,0.60)', end: 'rgba(241,196,15,0.60)'}}
        {k: 'Yellow (Heavy Tint)',  v: {start: 'rgba(170,138,10,0.90)', end: 'rgba(241,196,15,0.90)'}}
    ]},
    {isGroup: true, label: 'Swapbot Red', opts: [
        {k: 'Red (Light Tint)',     v: {start: 'rgba(191,39,24,0.30)',  end: 'rgba(231,76,6,0.30)'}}
        {k: 'Red (Medium Tint)',    v: {start: 'rgba(191,39,24,0.60)',  end: 'rgba(231,76,6,0.60)'}}
        {k: 'Red (Heavy Tint)',     v: {start: 'rgba(191,39,24,0.90)',  end: 'rgba(231,76,6,0.90)'}}
    ]},
]

botutils.defaultOverlay = ()->
    return settings[0].v

botutils.overlayOpts = () ->
    opts = []
    opts = [
        {k: '- No Overlay -', v: ''},
    ]
    for setting in settings
        opts.push(setting)

    return opts


botutils.overlayDesc = (value)->
    # console.log "overlayDesc value=",value
    desc = findSetting(value, settings)
    if desc
        return desc
    return 'No Overlay'


findSetting = (value, settings)->
    for setting in settings
        if setting.isGroup?
            res = findSetting(value, setting.opts)
            if res
                return res
            continue

        if setting.v.start == value?.start and setting.v.end == value?.end
            return setting.k
    
    return null        


module.exports = botutils
