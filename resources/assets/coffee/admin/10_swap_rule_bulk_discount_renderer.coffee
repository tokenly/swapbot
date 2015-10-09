# ---- begin references
constants = require './05_constants'
popoverLabels = require './05_popover_labels'
form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
swapbot = swapbot or {}; swapbot.swapUtils = require '../shared/swapUtils'
# ---- end references

# exports functions
exports = {}

buildBulkDiscountRows = (discountsProp)->
    discountsArray = discountsProp()

    out = []
    for discount, offset in discountsArray
        out.push(buildBulkDiscountRow(discountsProp, discount, offset))

    out.push(buildAddDiscountRow(discountsProp))

    return out


buildBulkDiscountRow = (discountsProp, discount, offset)->
    return m("tr", {}, [
        m("td", {}, m("div", {class: "discount-number"}, offset+1)),

        m("td", {}, form.mInputEl({id: "bd_moq_#{offset}"}, discount.moq)),
        m("td", {}, form.mInputEl({id: "bd_pct_#{offset}", postfix: '%', }, discount.pct)),

        m("td", {}, 
            m("a", {class: " remove-link remove-link-compact remove-link-discount", href: '#remove', onclick: buildRemoveRowFn(discountsProp, offset), }, [
                m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Discount #{offset}"}, ''),
            ]),
        ),

    ])

buildAddDiscountRow = (discountsProp)->
    discountsArray = []
    return m("tr", {}, [
        m("td", {colspan: 4}, 
            m("a", {class: "", href: '#add', onclick: buildAddRowFn(discountsProp)}, [
                m("span", {class: "glyphicon glyphicon-plus"}, ''),
                m("span", {}, " Add #{if discountsArray.length > 0 then "another" else "a"} Discount"),
            ]),
        ),

    ])


# ------------------------------------------------------------------------

buildAddRowFn = (discountsProp)->
    return (e)->
        e.preventDefault()

        discountsProp().push(exports.buildNewBulkDiscountRow())
        return

buildRemoveRowFn = (discountsProp, offsetToRemove)->
    return (e)->
        e.preventDefault()

        # filter the swaps to remove the offset
        filterFn = (discount, index)-> return (index != offsetToRemove)
        discountsProp(discountsProp().filter(filterFn))
        return

# debugDumpDiscounts = (discountsProp)->
#     out = "Showing #{discountsProp().length} discount(s)\n"
#     for discount, offset in discountsProp()
#         out += "##{offset}: #{discount.moq()} => #{discount.pct()}\n"
#     out = out.trim()
#     return out

# ------------------------------------------------------------------------

buildBulkDiscountRowsForDisplay = (discountsProp)->
    discountsArray = discountsProp()

    return discountsArray.map (discount, offset)->
        trEl = m("tr", {}, [
            m("td", {}, m("div", {class: "discount-number"}, offset+1)),

            m("td", {}, discount.moq()),
            m("td", {}, discount.pct()+"%"),
        ])
        return trEl


# ################################################

exports.buildNewBulkDiscountRow = ()->
    return {
        moq: m.prop(''),
        pct: m.prop(''),
    }

exports.renderBulkDiscountForm = (discountsProp)->
    bulkDiscountsTable = m("table", {class: "table"}, [
        m("thead", {}, [
            m("tr", {}, [
                m("th", {width: '10%',}, "#"),
                m("th", {width: '35%',}, "Minimum Order"),
                m("th", {width: '45%',}, "Percent Discount"),
                m("th", {width: '10%',}, ""),
            ]),
        ]),
        m("tbody", {}, buildBulkDiscountRows(discountsProp)),
    ])

    return m("div", { class: "row"}, [
        m("div", {class: "col-md-9"}, [
            form.composeFormField(
                form.mLabelEl(popoverLabels.advancedSwapBulkDiscounts),
                bulkDiscountsTable
            ),
        ]),
    ])

exports.renderBulkDiscountForDisplay = (rule)->
    discountsProp = rule.discounts
    bulkDiscountsTableForDisplay = m("table", {class: "table"}, [
        m("thead", {}, [
            m("tr", {}, [
                m("th", {width: '10%',}, "#"),
                m("th", {width: '40%',}, "Minimum Order"),
                m("th", {width: '45%',}, "Percent Discount"),
            ]),
        ]),
        m("tbody", {}, buildBulkDiscountRowsForDisplay(discountsProp)),
    ])

    return m("div", {class: 'advanced-swap-rule'}, [
        m("span", {class: 'number'}, ["Bulk Discount Rule ", m("span", class: 'rule-name', rule.name())]),
        m("div", { class: "row"}, [
            m("div", {class: "col-md-6"}, [
                bulkDiscountsTableForDisplay
            ]),
        ]),
    ])



module.exports = exports

