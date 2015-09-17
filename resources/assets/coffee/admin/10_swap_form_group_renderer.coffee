# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.constants = require './05_constants'
sbAdmin = sbAdmin or {}; sbAdmin.popoverLabels = require './05_popover_labels'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.popover = require './10_popover_utils'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
# ---- end references

# swapgrouprenderer functions
swapgrouprenderer = {}

constants = sbAdmin.constants
popoverLabels  = sbAdmin.popoverLabels

buildOnSwaptypeChange = (number, swap)->
    return (e)->
        value = e.srcElement.value
        if value == 'fiat'
            swap.in('BTC')
        return


sharedSwapTypeFormField = (number, swap)->
    offsetKey = buildOffsetKey(swap.direction(), number-1)
    action = (if swap.direction() == constants.DIRECTION_SELL then 'Sell' else 'Buy')
    return sbAdmin.form.mFormField(popoverLabels.swapTypeChoice(number, action), {
            onchange: buildOnSwaptypeChange(number, swap),
            id: "swap_strategy_#{offsetKey}",
            type: 'select',
            options: sbAdmin.swaputils.allStrategyOptions(swap.direction()),
        }
        , swap.strategy)



buildAddSwapFn = (vmProps, swapDirection)->
    return (e)->
        e.preventDefault()
        swapsProp = (if swapDirection == constants.DIRECTION_BUY then vmProps.buySwaps() else vmProps.sellSwaps())
        swapsProp.push(sbAdmin.swaputils.newSwapProp({direction: swapDirection}))
        return


buildRemoveSwapFn = (number, swapDirection, vmProps)->
    return (e)->
        e.preventDefault()

        # filter the swaps to remove the offset
        filterFn = (swap, index)->
            return (index != number - 1)
        if swapDirection == constants.DIRECTION_SELL
            vmProps.sellSwaps(vmProps.sellSwaps().filter(filterFn))
        else if swapDirection == constants.DIRECTION_BUY
            vmProps.buySwaps(vmProps.buySwaps().filter(filterFn))

        return



duplicateWarning = ()->
    return m("div", class: "duplicate-warning", [m('strong', {}, 'Warning:'), " This asset is received by 2 or more swaps. Multiple swaps will be triggered when this asset is received. This is not recommended."])



swapGroupRenderers = {rate: {}, fixed: {}, fiat: {}}

# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.rate.sell = (number, swap, vmProps, isDuplicate, offsetKey)->
    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mFormField(popoverLabels.rateSellTokenToSell, {id: "swap_out_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.out),
            ]),
            m("div", {class: "col-md-4"}, [
                sbAdmin.form.mFormField(popoverLabels.rateSellAssetToReceive, {id: "swap_in_#{offsetKey}", 'placeholder': "BTC", }, swap.in),
            ]),
        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-6"}, [
                sbAdmin.form.mFormField(popoverLabels.rateSellPrice, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in(), }, swap.price),
            ]),
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.rateSellMinimumSale, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in()}, swap.min),
            ]),
        ]
    }

swapGroupRenderers.rate.buy = (number, swap, vmProps, isDuplicate, offsetKey)->
    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mFormField(popoverLabels.rateBuyTokenToBuy, {id: "swap_in_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.in),
            ]),
            m("div", {class: "col-md-4"}, [
                sbAdmin.form.mFormField(popoverLabels.rateBuyAssetToPay, {id: "swap_out_#{offsetKey}", 'placeholder': "BTC", }, swap.out),
            ]),
        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-6"}, [
                sbAdmin.form.mFormField(popoverLabels.rateBuyPurchasePrice, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.out(), }, swap.rate),
            ]),
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.rateBuyMinimumSale, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in()}, swap.min),
            ]),
        ]
    }

# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fixed.sell = (number, swap, vmProps, isDuplicate, offsetKey)->
    return {
        leftColsWidth: 7
        leftCols: [
            m("div", {class: "col-md-4"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedSellTokenToSell, {id: "swap_out_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.out),
            ]),
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedSellAmountToSell, {type: "number", step: "any", min: "0", id: "swap_out_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(), }, swap.out_qty),
            ]),

        ],
        rightColsWidth: 5,
        rightCols: [
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedSellAssetToReceive, {id: "swap_in_#{offsetKey}", 'placeholder': "BTC", }, swap.in),
            ]),
            m("div", {class: "col-md-6"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedSellAmountToReceive, {type: "number", step: "any", min: "0", id: "swap_in_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.in(), }, swap.in_qty),
            ]),
        ]
    }



# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fixed.buy = (number, swap, vmProps, isDuplicate, offsetKey)->
    return {
        leftColsWidth: 7
        leftCols: [
            m("div", {class: "col-md-4"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedBuyTokenToBuy, {id: "swap_in_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.in),
            ]),
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedBuyAmountToBuy, {type: "number", step: "any", min: "0", id: "swap_in_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.in(), }, swap.in_qty),
            ]),

        ],
        rightColsWidth: 5,
        rightCols: [
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedBuyAssetToPay, {id: "swap_out_#{offsetKey}", 'placeholder': "BTC", }, swap.out),
            ]),
            m("div", {class: "col-md-6"}, [
                sbAdmin.form.mFormField(popoverLabels.fixedBuyAmountToPay, {type: "number", step: "any", min: "0", id: "swap_out_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(), }, swap.out_qty),
            ]),
        ]
    }


# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fiat.sell = (number, swap, vmProps, isDuplicate, offsetKey)->
    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-4"}, [
                sbAdmin.form.mFormField(popoverLabels.fiatSellSendsAsset, {id: "swap_out_#{offsetKey}", 'placeholder': "MYPRODUCT", }, swap.out),
            ]),
            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mValueDisplay(popoverLabels.fiatSellReceivesAsset, {id: "swap_in_#{offsetKey}", }, swap.in()),
            ]),

        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-4"}, [
                sbAdmin.form.mFormField(popoverLabels.fiatSellPrice, {type: "number", step: "any", min: "0", id: "swap_cost_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: 'USD'}, swap.cost),
            ]),
            m("div", {class: "col-md-5"}, [
                sbAdmin.form.mFormField(popoverLabels.fiatSellMinimumSale, {type: "number", step: "any", min: "0", id: "swap_min_out_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(),}, swap.min_out),
            ]),
            m("div", {class: "col-md-2"}, [
                sbAdmin.form.mFormField(popoverLabels.fiatSellIsDivisible, {type: "select", options: sbAdmin.form.yesNoOptions(), id: "swap_divisible_#{offsetKey}", }, swap.divisible),
            ]),
        ]
    }

# ------------------------------------------------------------------------------------------------------------------------


swapGroup = (number, swap, vmProps, isDuplicate)->
    swapDirection = swap().direction()
    offsetKey = buildOffsetKey(swapDirection, number-1)
    swapGroupSpec = swapGroupRenderers[swap().strategy()][swapDirection](number, swap(), vmProps, isDuplicate, offsetKey)
    return if not swapGroupSpec?


    swapGroupSpec.rightCols.push([
        # REMOVE LINK
        m("div", {class: "col-md-1"}, [
            m("a", {class: "remove-link pull-right", href: '#remove', onclick: buildRemoveSwapFn(number, swapDirection, vmProps), }, [
                m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Swap #{number}"}, ''),
            ]),
        ]),
    ])

    # typeName = (if swapDirection == constants.DIRECTION_SELL then 'Selling' else 'Purchasing')
    return m("div", {class: "asset-group#{if isDuplicate then ' duplicate-asset-group' else ''}"}, [
        # HEADER (removed)
        # m("h4", "#{typeName} Swap ##{number}")

        # BODY
        m("div", { class: "row"}, [
            m("div", {class: "col-md-#{swapGroupSpec.leftColsWidth}"}, m("div", { class: "row"}, swapGroupSpec.leftCols)),
            m("div", {class: "col-md-#{swapGroupSpec.rightColsWidth}"}, m("div", { class: "row"}, swapGroupSpec.rightCols)),
        ]),

        # DUPLICATE WARNING
        (if isDuplicate then duplicateWarning() else null),
    ])

buildOffsetKey = (swapDirection, offset)->
    return swapDirection+"_"+offset


# ################################################

swapgrouprenderer.buildSwapsSection = (swapDirection, duplicateSwapsOffsetsMap, vm)->
    vmProps = {sellSwaps: vm.sellSwaps, buySwaps: vm.buySwaps}

    if swapDirection == constants.DIRECTION_SELL
        swapsArray = vmProps.sellSwaps()
        addSwapFn = buildAddSwapFn(vmProps, constants.DIRECTION_SELL)
        action = "Selling"
    else
        swapsArray = vmProps.buySwaps()
        addSwapFn = buildAddSwapFn(vmProps, constants.DIRECTION_BUY)
        action = "Purchasing"

    

    return m("div", {}, [

        m("div", {class: "swap-groups"},
            swapsArray.map((swap, offset)->
                offsetKey = buildOffsetKey(swapDirection, offset)
                return swapGroup(offset+1, swap, vmProps, duplicateSwapsOffsetsMap[offsetKey]?)
            ),
        ),

        # add asset
        m("div", {class: "form-group add-swap-group"}, [
                m("a", {class: "", href: '#add', onclick: addSwapFn}, [
                    m("span", {class: "glyphicon glyphicon-plus"}, ''),
                    m("span", {}, " Add #{if swapsArray.length > 0 then "another" else "a"} #{action} Swap"),
                ]),
        ]),

    ])

module.exports = swapgrouprenderer

