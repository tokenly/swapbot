# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.csvutils = require './10_csv_utils'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
swapbot = swapbot or {}; swapbot.addressUtils = require '../shared/addressUtils'
# ---- end references

ctrl = {}

ctrl.allswaps = {}

# ################################################

vm = ctrl.allswaps.vm = do ()->
    vm = {}
    vm.init = ()->
        vm.user = m.prop(sbAdmin.auth.getUser())

        # init
        vm.swaps = m.prop([])
        vm.swapsRefreshing = m.prop('true')
        vm.swapFilterState = m.prop('confirming')

        # swaps
        vm.refreshSwaps()

        return

    vm.refreshSwapsFn = (e)->
        e.preventDefault()
        vm.refreshSwaps()
        return

    vm.refreshSwaps = ()->
        vm.swapsRefreshing('true')

        m.redraw(true)

        filters = {}
        if vm.swapFilterState().length
            filters.state = vm.swapFilterState()
        filters.sort = 'updatedAt'

        sbAdmin.api.getSwapsForAllUsers(filters).then (swapslist)->
            vm.swaps(swapslist)
            vm.swapsRefreshing(false)
            return

        return

    vm.changeFilterFn = (e)->
        e.preventDefault()
        setTimeout ()->
            vm.refreshSwaps()
            return
        , 1

    vm.exportAsCSV = (e)->
        # e.preventDefault()

        rows = []
        rows.push(['In Qty', 'In Asset','Out Qty','Out Asset','State','Updated','Bot','Owner',])
        for swap in vm.swaps()
            rows.push([
                "#{swap.receipt.quantityIn}",
                "#{swap.receipt.assetIn}",
                "#{swap.receipt.quantityOut}",
                "#{swap.receipt.assetOut}",
                swap.state,
                window.moment(swap.updatedAt).format('YYYY-MM-DD HH:mm:ss Z'),
                swap.botName,
                swap.botUsername,
            ])

        csvString = sbAdmin.csvutils.dataToCSVString(rows)
        csvHref = sbAdmin.csvutils.CSVDownloadHref(csvString)

        linkEl = e.target
        linkEl.setAttribute('download', 'export.csv')
        linkEl.setAttribute('href', csvHref)
        linkEl.setAttribute('target', '_blank')

        return

    return vm


ctrl.allswaps.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # init
    vm.init()

    return

ctrl.allswaps.view = ()->
    filterOptions = [
        {k: 'All Swaps', v: ''},
        {k: 'Brand New', v: 'brandnew'},
        {k: 'Out of Stock', v: 'outofstock'},
        {k: 'Out of Fuel', v: 'outoffuel'},
        {k: 'Ready', v: 'ready'},
        {k: 'Confirming', v: 'confirming'},
        {k: 'Sent', v: 'sent'},
        {k: 'Refunded', v: 'refunded'},
        {k: 'Complete', v: 'complete'},
        {k: 'Error', v: 'error'},
    ]

    filterSelectEl = sbAdmin.form.mInputEl({type: "select", options: filterOptions, id: "filter", onchange: vm.changeFilterFn }, vm.swapFilterState)

    if vm.swaps().length
        tableRows = vm.swaps().map((swap)->
            botAaddress = swapbot.addressUtils.publicBotHrefFromSwap(swap, window.location)

            return m("tr", {}, [
                m("td", {}, "#{swap.receipt.quantityIn} #{swap.receipt.assetIn}"),
                m("td", {}, "#{swap.receipt.quantityOut} #{swap.receipt.assetOut}"),
                m("td", {}, swap.state),
                m("td", {}, window.moment(swap.updatedAt).format('MMM D h:mm a')),
                m("td", {}, [
                    m("a[href='#{swapbot.addressUtils.publicSwapHref(swap)}']", {target: "_blank", class: "",}, 'Details'),
                ]),
                m("td", {}, [
                    m("a[href='/admin/swapevents/#{swap.id}']", {target: "_blank", class: "", config: m.route}, "Events"),

                ]),
                m("td", {}, [
                    m("a[href='#{botAaddress}']", {target: "_blank", class: "",}, swap.botName),
                    " | ",
                    m("a[href='/admin/view/bot/#{swap.botUuid}']", {class: "", config: m.route}, "Admin"),
                ]),
                m("td", {}, swap.botUsername),

            ])
        )
    else
        tableRows = m("tr", {}, [m('td', {colspan: 8, class: "not-found"}, 'No Swaps Found')])

    mEl = m("div", [
        m("h2", "All Swaps"),

        m("div", {class: "spacer1"}),
        

        m("p", {class: "pull-right"}, [m("a[href='#refresh']", {onclick: vm.refreshSwapsFn}, [m("span", {class: "glyphicon glyphicon-refresh", title: "Refresh"}, ''),' Refresh'])]),
        m("div", {class: "pull-right filter-select"}, [filterSelectEl]),
        m("p", {class: ""}, ["Here is a list of all Swaps.",]),

        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("table", {class: "striped-table swap-table #{if vm.swapsRefreshing() then 'refreshing' else ''}"}, [
                    m('thead', {}, [
                        m('tr', {}, [
                            m('th', {}, 'In'),
                            m('th', {}, 'Out'),
                            m('th', {}, 'State'),
                            m('th', {}, 'Updated'),
                            m('th', {}, 'Details'),
                            m('th', {}, 'Events'),
                            m('th', {}, 'Bot'),
                            m('th', {}, 'Owner'),
                        ]),
                    ]),
                    m('tbody', {}, tableRows),
                ]),
            ]),
        ]),
            

        m("div", {class: "spacer2"}),

        m("a[href='#csvExport']", {class: "btn btn-success", onclick: vm.exportAsCSV }, "Download as CSV"),

        m("div", {class: "spacer1"}),
    ])

    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.allswaps
