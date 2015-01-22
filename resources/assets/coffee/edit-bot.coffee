do ($=jQuery)->
    console.log "edit bot says hi"

    init = ()->
        bindAssetGroups()
        return




    # ##################################################################
    # Asset groups

    MAX_ASSETS = 5
    assetGroupsShownCount = 0
    
    bindAssetGroups = ()->
        showAssets(1)

        $('a[data-add-asset]').on 'click', (e)->
            e.preventDefault()

            newAssetNumber = assetGroupsShownCount + 1
            if newAssetNumber <= MAX_ASSETS
                showAssets(newAssetNumber)

            if newAssetNumber >= MAX_ASSETS
                $(this).hide()

            return

    showAssets = (maxAssetToShow)->
        $('div[data-asset-group]').each ()->
            groupEL = $(this)
            assetNumber = groupEL.data('asset-group')
            if assetNumber <= maxAssetToShow
                groupEL.show()
            else
                groupEL.hide()

            return
        
        assetGroupsShownCount = maxAssetToShow
        return


    # ##################################################################
    # init

    init()