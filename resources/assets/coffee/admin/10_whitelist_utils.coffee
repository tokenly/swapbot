# ---- begin references
SwapbotAPI = require './10_api_functions'
# ---- end references

# whitelist functions
exports = {}

exports.populateWhitelistOptions = (whitelistProp)->
    opts = []
    opts.push({k: 'Loading...', v: ''})

    SwapbotAPI.getAllWhitelistNames().then(
        (whitelists)->
            console.log "whitelists=", whitelists
            opts = []
            opts.push({k: '- None -', v: ''})
            whitelists.map (whitelist)->
                opts.push({k: whitelist.name, v: whitelist.id})
                return
            
            whitelistProp(opts)

            return
        , (errorResponse)->
            console.error(errorResponse.errors)
            opts = []
            opts.push({k: 'Error Loading Whitelists...', v: ''})
            whitelistProp(opts)
            return
    )

    opts.push({k: '- No Whitelists Available -', v: ''})
    whitelistProp(opts)
    return
    
exports.resolveWhitelistName = (uuid, whitelistNameProp)->
    SwapbotAPI.getAllWhitelistNames().then(
        (whitelists)->
            whitelists.map (whitelist)->
                if whitelist.id == uuid
                    whitelistNameProp(whitelist.name)
                return
            return
        , (errorResponse)->
            console.error(errorResponse.errors)
            return
    )


# ------------------------------------------------------------------------
module.exports = exports
