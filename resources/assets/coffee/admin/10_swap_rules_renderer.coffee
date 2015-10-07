# ---- begin references
constants = require './05_constants'
popoverLabels = require './05_popover_labels'
form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.popover = require './10_popover_utils'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
swapbot = swapbot or {}; swapbot.swapUtils = require '../shared/swapUtils'
bulkDiscountRenderer = require './10_swap_rule_bulk_discount_renderer'
uuid = require '../shared/uuid'
formatters = require '../shared/formatters'
# ---- end references

# exports functions
exports = {}

ruleOptions = ()->
    return [
        {k: "- Choose One -", v: ""},
        {k: "Bulk Discount",   v: "bulkDiscount"},
    ]

renderRuleGroup = (offset, rulesProp)->
    rule = rulesProp()[offset]
    return m("div", { class: "item-group"}, [
        m("div", { class: "rule row"}, [

            m("div", {class: "col-md-2"}, [
                form.mFormField(
                    popoverLabels.buildAdvancedSwapRuleType(offset), 
                    {
                        id: "swap_rule_type_#{offset}",
                        type: 'select',
                        options: ruleOptions(),
                    },
                    rule.ruleType
                )
            ]),

            m("div", {class: "col-md-3"}, [
                form.mFormField(
                    popoverLabels.swapRuleName, 
                    {
                        id: "swap_rule_name_#{offset}",
                    },
                    rule.name
                )
            ]),

            m("div", {class: "col-md-6"}, [
                buildRuleContent(rule)
            ]),

            m("div", {class: "col-md-1"}, [
                m("a", {class: "remove-link", href: '#remove', onclick: buildRemoveRuleFn(offset, rulesProp), }, [
                    m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Rule #{offset+1}"}, ''),
                ]),
            ]),
        ]),
    ])


# ------------------------------------------------------------------------

buildRuleContent = (rule)->
    ruleType = rule.ruleType()
    # console.log "ruleType=#{ruleType}"
    if ruleBuilders[ruleType]?
        return ruleBuilders[ruleType](rule)

    return form.composeFormField(
        m("label", {class: "control-label inactive-rule"}, [
            "Rule Definition"
        ]),
        m("div", {class: 'inactive-rule inactive-rule-value'}, "Please choose a rule type.")
    )


ruleBuilders = {}

ruleBuilders.bulkDiscount = (rule)->
    return bulkDiscountRenderer.renderBulkDiscountForm(rule.discounts)







# ------------------------------------------------------------------------

buildAddRuleFn = (rulesProp)->
    return (e)->
        e.preventDefault()

        rulesProp().push(createNewEmptyRule(rulesProp))
        return

buildRemoveRuleFn = (offset, rulesProp)->
    return (e)->
        e.preventDefault()

        # filter the swaps to remove the offset
        filterFn = (swap, index)-> return (index != offset)
        rulesProp(rulesProp().filter(filterFn))
        return


createNewEmptyRule = (rulesProp)->
    newRule = {
        uuid: m.prop(uuid.uuid4()),
        name: m.prop('Bulk Discount Rule #'+(rulesProp().length+1)),
        ruleType: m.prop('bulkDiscount'),
    }

    if newRule.ruleType() == 'bulkDiscount'
        newRule.discounts = m.prop([bulkDiscountRenderer.buildNewBulkDiscountRow()])

    return newRule

createNewRuleFromData = (ruleData)->
    newRule = {
        uuid: m.prop(ruleData.uuid),
        name: m.prop(ruleData.name),
        ruleType: m.prop(ruleData.ruleType),
    }

    if ruleData.ruleType == 'bulkDiscount'
        if ruleData.discounts?
            discounts = []
            for discountData in ruleData.discounts
                discounts.push({
                    moq: m.prop(discountData.moq)
                    pct: m.prop(formatters.formatPercentage(discountData.pct * 100))
                })
            newRule.discounts = m.prop(discounts)
        else
            newRule.discounts = m.prop([bulkDiscountRenderer.buildNewBulkDiscountRow()])

    return newRule

# ################################################

renderRuleForDisplay = (rule, offset)->
    ruleType = rule.ruleType()
    if ruleRenderers[ruleType]?
        return ruleRenderers[ruleType](rule, offset)
    return "unknown rule type #{ruleType}"

ruleRenderers = {}

ruleRenderers.bulkDiscount = (rule, offset)->
    return m("div", {class: "swap-rule"}, [
        bulkDiscountRenderer.renderBulkDiscountForDisplay(rule)
    ])


# ################################################

exports.buildRulesSection = (rulesProp)->
    rulesArray = rulesProp()

    return m("div", {}, [

        m("div", {class: "items-group"},
            rulesArray.map((rule, offset)->
                return renderRuleGroup(offset, rulesProp)
            ),
        ),

        # add asset
        m("div", {class: "form-group add-rules-group"}, [
                m("a", {class: "", href: '#add', onclick: buildAddRuleFn(rulesProp)}, [
                    m("span", {class: "glyphicon glyphicon-plus"}, ''),
                    m("span", {}, " Add #{if rulesArray.length > 0 then "another" else "an"} Advanced Swap Rule"),
                ]),
        ]),

    ])

exports.buildRulesForDisplay = (rulesProp)->
    rulesArray = rulesProp()

    if rulesArray.length <= 0
        return m("div", {class: "swap-rules-group no-rules"}, "This bot has no advanced swap rules")

    return m("div", {class: "swap-rules-group"}, [
        rulesArray.map (rule, offset)->
            renderRuleForDisplay(rule, offset)
    ])

exports.serialize = (rulesProp)->
    rules = JSON.parse(JSON.stringify(rulesProp()))
    out = rules.map (rule, index)->
        if rule.ruleType == 'bulkDiscount'
            rule.discounts = rule.discounts.map (discount, index)->
                pct = discount.pct
                if pct and not isNaN(pct)
                    discount.pct = pct / 100
                return discount

        return rule
    return out

exports.unserialize = (rulesData)->
    if not rulesData? then return []

    swapRulesArray = rulesData.map (ruleData, index)->
        return createNewRuleFromData(ruleData)

    return swapRulesArray


module.exports = exports

