# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.constants = require './05_constants'
sbAdmin = sbAdmin or {}; sbAdmin.popoverLabels = require './05_popover_labels'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
swapbot = swapbot or {}; swapbot.swapUtils = require '../shared/swapUtils'
swapRulesRenderer = require './10_swap_rules_renderer'
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

            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.fiatSellSendsAsset, {id: "swap_out_#{offsetKey}", 'placeholder': "MYPRODUCT", }, swap.out),
            ]),
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.fiatSellReceivesAsset, {id: "swap_in_#{offsetKey}", type: 'select', options: vmProps.pricedTokenOpts() }, swap.in),
            ]),

        ],
        rightColsWidth: 6,
        rightCols: [
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.fiatSellPrice, {type: "number", step: "any", min: "0", id: "swap_cost_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: 'USD'}, swap.cost),
            ]),
            m("div", {class: "col-md-4"}, [
                formFieldFn(popoverLabels.fiatSellMinimumSale, {type: "number", step: "any", min: "0", id: "swap_min_out_#{offsetKey}", 'placeholder': "1", postfixLimit: 7, postfix: swap.out(),}, swap.min_out),
            ]),
            m("div", {class: "col-md-3"}, [
                formFieldFn(popoverLabels.fiatSellIsDivisible, {type: "select", options: sbAdmin.form.yesNoOptions(), id: "swap_divisible_#{offsetKey}", }, swap.divisible),
            ]),
        ]
    }

# ------------------------------------------------------------------------------------------------------------------------

renderSwapRules = (number, swap, vmProps, swapDirection)->


    # vmProps.swapRules()
    if not swap().swapRules then swap().swapRules = m.prop([])

    numberOfAvailableRules = vmProps.swapRules()?.length
    numberOfAppliedRules = swap().swapRules()?.length

    hasAnyAppliedRules = (numberOfAppliedRules > 0)

    # if no rules can be applied, then don't show anything
    swapRuleCanBeApplied = false
    if swapDirection == constants.DIRECTION_SELL and numberOfAvailableRules > 0
        swapRuleCanBeApplied = true
    if not swapRuleCanBeApplied then return null

    allowAdd = false
    if numberOfAvailableRules > 1 and numberOfAvailableRules > numberOfAppliedRules
            allowAdd = true
    if swap().swapRules()?.length < 1 then allowAdd = true

    return m("div", class: "choose-swap-rules row", [
        if hasAnyAppliedRules then (
            m("div", { class: "col-md-3 advanced-swap-rules-label"}, [
                "Advanced Rules for Swap #{number}"
            ])
        ),
        m("div", { class: "col-md-#{if hasAnyAppliedRules then '9' else '12'}"}, 
            (
                if swapRuleCanBeApplied then (
                    [
                        m("span", class: "applied-rules",
                            renderAppliedSwapRules(swap, vmProps, swapDirection)
                        ),

                        if allowAdd then m("a", {class: "add-swap-rule-link", href: '#add', onclick: buildAddSwapRuleFn(swap, vmProps, swapDirection), }, [
                            m("span", {class: "glyphicon glyphicon-plus", title: "Apply an Advanced Swap Rule to Swap ##{number}"}),
                            [" Apply an Advanced Rule to Swap ##{number}"]
                        ]),
                    ]
                ) else m("span", class: "no-applied-rules",
                        "No advanced swap rules are available.  You can add one below."
                    )
            )
        ),
    ])

    return

renderAppliedSwapRules = (swap, vmProps, swapDirection)->
    hasMultipleAvailableRules = (vmProps.swapRules()?.length > 1)
    return swap().swapRules().map (advancedSwapRule, index)->
        return m("span", class: "applied-rule", [
            if hasMultipleAvailableRules then m("a", {class: "prev-swap-rule-link", href: '#previous', onclick: buildPrevSwapRuleFn(swap, advancedSwapRule, index, vmProps, swapDirection), }, [
                m("span", {class: "glyphicon glyphicon-triangle-left", title: "Previous Advanced Swap Rule"}),
            ]),
            "#{advancedSwapRule.name()}",
            if hasMultipleAvailableRules then m("a", {class: "next-swap-rule-link", href: '#next', onclick: buildNextSwapRuleFn(swap, advancedSwapRule, index, vmProps, swapDirection), }, [
                m("span", {class: "glyphicon glyphicon-triangle-right", title: "Next Advanced Swap Rule"}),
            ]),
            m("a", {class: "delete-swap-rule-link", href: '#remove', onclick: buildRemoveSwapRuleFn(index, swap, vmProps, swapDirection), }, [
                m("span", {class: "glyphicon glyphicon-remove", title: "Remove Advanced Swap Rule"}),
            ]),
        ])

buildAddSwapRuleFn = (swap, vmProps)->
    return (e)->
        e.preventDefault()

        if not swap().swapRules then swap().swapRules = m.prop([])
        newSwapRulesArray = swap().swapRules()
        newSwapRulesArray.push(buildNewAdvancedSwapRuleToAdd(swap, vmProps))
        swap().swapRules(newSwapRulesArray)

        # updateSwap(swap, vmProps)

        return

buildPrevSwapRuleFn = (swap, currentAdvancedSwapRule, currentIndex, vmProps, swapDirection)->
    return buildChangeSwapRuleFn(swap, currentAdvancedSwapRule, currentIndex, vmProps, swapDirection, false)

buildNextSwapRuleFn = (swap, currentAdvancedSwapRule, currentIndex, vmProps, swapDirection)->
    return buildChangeSwapRuleFn(swap, currentAdvancedSwapRule, currentIndex, vmProps, swapDirection, true)


buildChangeSwapRuleFn = (swap, currentAdvancedSwapRule, currentIndex, vmProps, swapDirection, isForward)->
    return (e)->
        e.preventDefault()

        allAdvancedSwapRules = vmProps.swapRules()
        currentOffset = -1
        maxOffset = allAdvancedSwapRules.length - 1
        allAdvancedSwapRules.map (rule, offset)->
            if currentAdvancedSwapRule.uuid() == rule.uuid()
                currentOffset = offset
        nextOffset = currentOffset + (if isForward then 1 else -1)
        if nextOffset > maxOffset
            nextOffset = 0
        if nextOffset < 0
            nextOffset = maxOffset

        updatedSwapRulesArray = swap().swapRules()
        updatedSwapRulesArray[currentIndex] = allAdvancedSwapRules[nextOffset]
        swap().swapRules(updatedSwapRulesArray)

        # updateSwap(swap, vmProps)

        return

buildRemoveSwapRuleFn = (indexToRemove, swap, vmProps, swapDirection)->
    return (e)->
        e.preventDefault()

        filterFn = (swapRule, index)->
            return (index != indexToRemove)
        swap().swapRules(swap().swapRules().filter(filterFn))

        # updateSwap(swap, vmProps)

        return


buildNewAdvancedSwapRuleToAdd = (swap, vmProps)->
    return vmProps.swapRules()[0]

# ------------------------------------------------------------------------------------------------------------------------


swapGroup = (number, swap, vmProps, swapDirection, isDuplicate)->
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
    return m("div", {class: "item-group#{if isDuplicate then ' duplicate-asset-group' else ''}"}, [
        # HEADER (removed)
        # m("h4", "#{typeName} Swap ##{number}")

        # BODY
        m("div", { class: "row"}, [
            m("div", {class: "col-md-#{swapGroupSpec.leftColsWidth}"}, m("div", { class: "row"}, swapGroupSpec.leftCols)),
            m("div", {class: "col-md-#{swapGroupSpec.rightColsWidth}"}, m("div", { class: "row"}, swapGroupSpec.rightCols)),
        ]),

        # Advanced rule
        renderSwapRules(number, swap, vmProps, swapDirection),

        # DUPLICATE WARNING
        (if isDuplicate then duplicateWarning() else null),
    ])

swapGroupForDisplayProse = (swap, swapRulesProp)->
    flatSwapConfig = JSON.parse(JSON.stringify(swap))
    swapProse = swapbot.swapUtils.swapDetailsProse(flatSwapConfig)

    ruleDescriptions = swap().swapRules().map (swapRule, index)->
        # console.log "swapRule=", swapRule
        typeDesc = ''
        if swapRule.ruleType() == 'bulkDiscount' then typeDesc = 'bulk discount'
        return m("span", { class: 'applied-rule-desc'}, [
            m("span", {class: "glyphicon glyphicon-transfer"}, ''),
            " This swap uses then #{typeDesc} rule named ",
            m("span", { class: 'rule-name'}, [
                swapRule.name()
            ]),
            "."
        ])

    return [
        swapProse, 
        if ruleDescriptions.length > 0 then (
            m("span", {class: 'applied-rule-descriptions'}, ruleDescriptions))
        else null
    ]


buildOffsetKey = (swapDirection, offset)->
    return swapDirection+"_"+offset




# updateSwap = (swap, vmProps)->
#     console.log "ignoring updateSwap"
#     targetOffset = swap().offset()

#     updateFn = (swapsIn)->
#         return swapsIn.map (oldSwap, offset)->
#             if offset == targetOffset then return swap
#             return oldSwap

#     swapDirection = swap().direction()
#     if swapDirection == constants.DIRECTION_SELL
#         vmProps.sellSwaps(updateFn(vmProps.sellSwaps()))
#     else
#         vmProps.buySwaps(updateFn(vmProps.buySwaps()))

#     return


# ################################################

swapgrouprenderer.buildSwapsSection = (swapDirection, duplicateSwapsOffsetsMap, vm)->
    vmProps = {sellSwaps: vm.sellSwaps, buySwaps: vm.buySwaps, swapRules: vm.swapRules, pricedTokenOpts: vm.pricedTokenOpts}

    addTempIDFn = (swapsArray)->
        return swapsArray.map (swap, offset)->
            swap().offset = m.prop(offset)
            return swap

    if swapDirection == constants.DIRECTION_SELL
        swapsArray = vmProps.sellSwaps(addTempIDFn(vmProps.sellSwaps()))
        addSwapFn = buildAddSwapFn(vmProps, constants.DIRECTION_SELL)
        action = "Selling"
    else
        swapsArray = vmProps.buySwaps(addTempIDFn(vmProps.buySwaps()))
        addSwapFn = buildAddSwapFn(vmProps, constants.DIRECTION_BUY)
        action = "Purchasing"


    return m("div", {}, [

        m("div", {class: "swap-groups"},
            swapsArray.map((swap, offset)->
                offsetKey = buildOffsetKey(swapDirection, offset)
                return swapGroup(offset+1, swap, vmProps, swapDirection, duplicateSwapsOffsetsMap[offsetKey]?)
            ),
        ),

        # add asset
        m("div", {class: "form-group add-item-group"}, [
                m("a", {class: "", href: '#add', onclick: addSwapFn}, [
                    m("span", {class: "glyphicon glyphicon-plus"}, ''),
                    m("span", {}, " Add #{if swapsArray.length > 0 then "another" else "a"} #{action} Swap"),
                ]),
        ]),

    ])

swapgrouprenderer.buildSwapsSectionForDisplay = (swapDirection, swapsArray, swapRulesProp)->
    swapsForDirectionCount = 0
    return m("div", {class: "swap-groups"}, [
        swapsArray.map((swap, offset)->
            if swap().direction() == swapDirection
                ++swapsForDirectionCount
                return m("div", {class: "swap-group"}, [
                    m("div", {class: "swap-group"}, [
                        m("span", {class: 'number'}, "Swap ##{offset+1} "),
                        swapGroupForDisplayProse(swap, swapRulesProp),
                    ])
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

