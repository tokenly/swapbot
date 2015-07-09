do ()->

    sbAdmin.ctrl.allswaps = {}

    # ################################################

    vm = sbAdmin.ctrl.allswaps.vm = do ()->
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




        return vm


    sbAdmin.ctrl.allswaps.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        # init
        vm.init()

        return

    sbAdmin.ctrl.allswaps.view = ()->
        filterOptions = [
            {k: 'All Swaps', v: ''},
            {k: 'Brand New', v: 'brandnew'},
            {k: 'Out of Stock', v: 'outofstock'},
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
                # address = swapbot.addressUtils.publicBotAddress(swap.username, swap.id, window.location)
                botAaddress = swapbot.addressUtils.publicBotAddress(swap.botUsername, swap.botUuid, window.location)
                return m("tr", {}, [
                    m("td", {}, "#{swap.receipt.quantityIn} #{swap.receipt.assetIn}"),
                    m("td", {}, "#{swap.receipt.quantityOut} #{swap.receipt.assetOut}"),
                    m("td", {}, swap.state),
                    m("td", {}, window.moment(swap.updatedAt).format('MMM D h:mm a')),
                    m("td", {}, [
                        m("a[href='/public/#{swap.botUsername}/swap/#{swap.id}']", {target: "_blank", class: "",}, 'Details'),
                    ]),
                    m("td", {}, [
                        m("a[href='/admin/swapevents/#{swap.id}']", {target: "_blank", class: "", config: m.route}, "Events"),

                    ]),
                    m("td", {}, [
                        m("a[href='#{botAaddress}']", {target: "_blank", class: "",}, swap.botName),
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
                

            m("div", {class: "spacer1"}),

            
        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


    ######