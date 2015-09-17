# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.constants = require './05_constants'
sbAdmin = sbAdmin or {}; sbAdmin.popoverLabels = require './05_popover_labels'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.popover = require './10_popover_utils'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
swapbot = swapbot or {}; swapbot.swapUtils = require '../shared/swapUtils'
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
    formFieldFn = sbAdmin.form.mFormField

    offsetKey = buildOffsetKey(swap.direction(), number-1)
    action = (if swap.direction() == constants.DIRECTION_SELL then 'Sell' else 'Buy')
    return formFieldFn(popoverLabels.swapTypeChoice(number, action), {
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
swapGroupRenderers.rate.sell = (number, swap, vmProps, offsetKey)->
    formFieldFn = sbAdmin.form.mFormField

    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.rateSellTokenToSell, {id: "swap_out_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.out),
            ]),
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.rateSellAssetToReceive, {id: "swap_in_#{offsetKey}", 'placeholder': "BTC", }, swap.in),
            ]),
        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-6"}, [
                formFieldFn(popoverLabels.rateSellPrice, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in(), }, swap.price),
            ]),
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.rateSellMinimumSale, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in()}, swap.min),
            ]),
        ]
    }


swapGroupRenderers.rate.buy = (number, swap, vmProps, offsetKey)->
    formFieldFn = sbAdmin.form.mFormField

    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.rateBuyTokenToBuy, {id: "swap_in_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.in),
            ]),
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.rateBuyAssetToPay, {id: "swap_out_#{offsetKey}", 'placeholder': "BTC", }, swap.out),
            ]),
        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-6"}, [
                formFieldFn(popoverLabels.rateBuyPurchasePrice, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.out(), }, swap.rate),
            ]),
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.rateBuyMinimumSale, {type: "number", step: "any", min: "0", id: "swap_rate_#{offsetKey}", 'placeholder': "0.000001", postfixLimit: 7, postfix: swap.in()}, swap.min),
            ]),
        ]
    }

# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fixed.sell = (number, swap, vmProps, offsetKey)->
    formFieldFn = sbAdmin.form.mFormField

    return {
        leftColsWidth: 7
        leftCols: [
            m("div", {class: "col-md-4"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.fixedSellTokenToSell, {id: "swap_out_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.out),
            ]),
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.fixedSellAmountToSell, {type: "number", step: "any", min: "0", id: "swap_out_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(), }, swap.out_qty),
            ]),

        ],
        rightColsWidth: 5,
        rightCols: [
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.fixedSellAssetToReceive, {id: "swap_in_#{offsetKey}", 'placeholder': "BTC", }, swap.in),
            ]),
            m("div", {class: "col-md-6"}, [
                formFieldFn(popoverLabels.fixedSellAmountToReceive, {type: "number", step: "any", min: "0", id: "swap_in_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.in(), }, swap.in_qty),
            ]),
        ]
    }



# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fixed.buy = (number, swap, vmProps, offsetKey)->
    formFieldFn = sbAdmin.form.mFormField

    return {
        leftColsWidth: 7
        leftCols: [
            m("div", {class: "col-md-4"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.fixedBuyTokenToBuy, {id: "swap_in_#{offsetKey}", 'placeholder': "LTBCOIN", }, swap.in),
            ]),
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.fixedBuyAmountToBuy, {type: "number", step: "any", min: "0", id: "swap_in_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.in(), }, swap.in_qty),
            ]),

        ],
        rightColsWidth: 5,
        rightCols: [
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.fixedBuyAssetToPay, {id: "swap_out_#{offsetKey}", 'placeholder': "BTC", }, swap.out),
            ]),
            m("div", {class: "col-md-6"}, [
                formFieldFn(popoverLabels.fixedBuyAmountToPay, {type: "number", step: "any", min: "0", id: "swap_out_qty_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(), }, swap.out_qty),
            ]),
        ]
    }


# ------------------------------------------------------------------------------------------------------------------------
swapGroupRenderers.fiat.sell = (number, swap, vmProps, offsetKey)->
    formFieldFn = sbAdmin.form.mFormField

    return {
        leftColsWidth: 6
        leftCols: [
            m("div", {class: "col-md-5"}, [sharedSwapTypeFormField(number, swap),]),

            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.fiatSellSendsAsset, {id: "swap_out_#{offsetKey}", 'placeholder': "MYPRODUCT", }, swap.out),
            ]),
            m("div", {class: "col-md-3"}, [
                sbAdmin.form.mValueDisplay(popoverLabels.fiatSellReceivesAsset, {id: "swap_in_#{offsetKey}", }, swap.in()),
            ]),

        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.fiatSellPrice, {type: "number", step: "any", min: "0", id: "swap_cost_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: 'USD'}, swap.cost),
            ]),
            m("div", {class: "col-md-5"}, [
                formFieldFn(popoverLabels.fiatSellMinimumSale, {type: "number", step: "any", min: "0", id: "swap_min_out_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(),}, swap.min_out),
            ]),
            m("div", {class: "col-md-2"}, [
                formFieldFn(popoverLabels.fiatSellIsDivisible, {type: "select", options: sbAdmin.form.yesNoOptions(), id: "swap_divisible_#{offsetKey}", }, swap.divisible),
            ]),
        ]
    }

# ------------------------------------------------------------------------------------------------------------------------


swapGroup = (number, swap, vmProps, isDuplicate)->
    swapDirection = swap().direction()
    offsetKey = buildOffsetKey(swapDirection, number-1)
    swapGroupSpec = swapGroupRenderers[swap().strategy()][swapDirection](number, swap(), vmProps, offsetKey)
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

swapGroupForDisplayProse = (swap)->
    flatSwapConfig = {}
    for k, v of swap()
        flatSwapConfig[k] = v()
    return swapbot.swapUtils.swapDetailsProse(flatSwapConfig)


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

swapgrouprenderer.buildSwapsSectionForDisplay = (swapDirection, swapsArray)->
    swapsForDirectionCount = 0
    return m("div", {class: "swap-groups"}, [
        swapsArray.map((swap, offset)->
            if swap().direction() == swapDirection
                ++swapsForDirectionCount
                return m("div", {class: "swap-group"}, [
                    m("span", {class: 'number'}, "Swap ##{offset+1} "),
                    swapGroupForDisplayProse(swap),
                ])
            ),

        (
            if swapsForDirectionCount == 0
                m("div", {class: "no-swap-groups"}, [
                    "There are no swaps to #{if swapDirection == constants.DIRECTION_SELL then 'sell' else 'purchase'} tokens."
                ])
        ),
    ])

    # return m("div", {}, "swaps here...")

module.exports = swapgrouprenderer

