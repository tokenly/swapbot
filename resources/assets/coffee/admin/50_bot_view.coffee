do ()->

    sbAdmin.ctrl.botView = {}

    # ### helpers #####################################
    swapGroupRenderers = {}

    swapGroupRenderers.rate = (number, swap)->
        return m("div", {class: "asset-group"}, [
            m("h4", "Swap ##{number}"),
            m("div", { class: "row"}, [
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mValueDisplay("Swap Type", {id: "swap_strategy_#{number}",}, sbAdmin.swaputils.strategyLabelByValue(swap.strategy())),
                ]),
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mValueDisplay("Receives Asset", {id: "swap_in_#{number}", }, swap.in()),
                ]),
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mValueDisplay("Sends Asset", {id: "swap_out_#{number}", }, swap.out()),
                ]),
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mValueDisplay("Rate", {type: "number", step: "any", min: "0", id: "swap_rate_#{number}", }, swap.rate()),
                ]),
            ]),
        ])

    swapGroupRenderers.fixed = (number, swap)->
        return m("div", {class: "asset-group"}, [
            m("h4", "Swap ##{number}"),
            m("div", { class: "row"}, [
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mValueDisplay("Swap Type", {id: "swap_strategy_#{number}",}, sbAdmin.swaputils.strategyLabelByValue(swap.strategy())),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mValueDisplay("Receives Asset", {id: "swap_in_#{number}", }, swap.in()),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mValueDisplay("Receives Quantity", {id: "swap_in_qty_#{number}", }, swap.in_qty()),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mValueDisplay("Sends Asset", {id: "swap_out_#{number}", }, swap.out()),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mValueDisplay("Sends Quantity", {id: "swap_out_qty_#{number}", }, swap.out_qty()),
                ]),
            ]),
        ])

    swapGroup = (number, swapProp)->
        return swapGroupRenderers[swapProp().strategy()](number, swapProp())

    serializeSwaps = (swap)->
        out = []
        out.push(swap)
        return out

    subscribeToPusherChannel = (channelName, callbackFn)->
        client = new window.Faye.Client("#{window.PUSHER_URL}/public")
        client.subscribe "/#{channelName}", (data)->
            callbackFn(data)
            return
        return client

    closePusherChannel = (client)->
        client.disconnect()
        return



    handleBotEventMessage = (data)->
        # console.log "pusher received:", data
        # console.log "msg:", data?.event?.msg
        if data?.event?.msg
            vm.botEvents().unshift(data)
            # this is outside of mithril, so we must force a redraw
            m.redraw(true)
        return

    handleBotBalancesMessage = (data)->
        # console.log "bot balances:",data
        if data?
            vm.updateBalances(data)
            # this is outside of mithril, so we must force a redraw
            m.redraw(true)
        return


    buildMLevel = (levelNumber)->
        switch levelNumber
            when 100 then return m('span', {class: "label label-default debug"}, "Debug")
            when 200 then return m('span', {class: "label label-info info"}, "Info")
            when 250 then return m('span', {class: "label label-primary primary"}, "Notice")
            when 300 then return m('span', {class: "label label-warning warning"}, "Warning")
            when 400 then return m('span', {class: "label label-danger danger"}, "Error")
            when 500 then return m('span', {class: "label label-danger danger"}, "Critical")
            when 550 then return m('span', {class: "label label-danger danger"}, "Alert")
            when 600 then return m('span', {class: "label label-danger danger"}, "Emergency")
        return m('span', {class: "label label-danger danger"}, "Code #{levelNumber}")

    buildBalancesMElement = (balances)->
        if vm.balances().length > 0
            return m("table", {class: "table table-condensed table-striped"}, [
                m("thead", {}, [
                    m("tr", {}, [
                        m('th', {style: {width:'40%'}}, 'Asset'),
                        m('th', {style: {width:'60%'}}, 'Balance'),
                    ]),
                ]),
                m("tbody", {}, [
                    vm.balances().map (balance, index)->
                        return m("tr", {}, [
                            m('td', balance.asset),
                            m('td', balance.val),
                        ])
                ]),
            ])
        else
            return m("div", {class: "form-group"}, "No Balances Found")

    # ################################################

    vm = sbAdmin.ctrl.botView.vm = do ()->
        buildSwapsPropValue = (swaps)->
            out = []
            for swap in swaps
                out.push(sbAdmin.swaputils.newSwapProp(swap))
            return out


        buildBalancesPropValue = (balances)->
            out = []
            for asset, val of balances
                out.push({
                    asset: asset
                    val: val
                })
            # console.log "buildBalancesPropValue out=",out
            return out

        vm = {}

        vm.updateBalances = (newBalances)->
            vm.balances(buildBalancesPropValue(newBalances))
            return

        vm.toggleDebugView = (e)->
            e.preventDefault()
            vm.showDebug = !vm.showDebug
            return


        vm.init = ()->
            # view status
            vm.errorMessages = m.prop([])
            vm.formStatus = m.prop('active')
            vm.resourceId = m.prop('new')
            vm.pusherClient = m.prop(null)
            vm.botEvents = m.prop([])
            vm.showDebug = false

            # fields
            vm.name = m.prop('')
            vm.description = m.prop('')
            vm.address = m.prop('')
            vm.active = m.prop('')
            vm.swaps = m.prop(buildSwapsPropValue([]))
            vm.balances = m.prop(buildBalancesPropValue([]))

            # if there is an id, then load it from the api
            id = m.route.param('id')
            # load the bot info from the api
            sbAdmin.api.getBot(id).then(
                (botData)->
                    # console.log "botData", botData
                    vm.resourceId(botData.id)

                    vm.name(botData.name)
                    vm.address(botData.address)
                    vm.active(botData.active)
                    vm.description(botData.description)
                    vm.swaps(buildSwapsPropValue(botData.swaps))
                    vm.balances(buildBalancesPropValue(botData.balances))

                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )

            # also get the bot events
            sbAdmin.api.getBotEvents(id).then(
                (apiResponse)->
                    vm.botEvents(apiResponse)
                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )

            vm.pusherClient(subscribeToPusherChannel("swapbot_events_#{id}", handleBotEventMessage))
            vm.pusherClient(subscribeToPusherChannel("swapbot_balances_#{id}", handleBotBalancesMessage))
            # console.log "vm.pusherClient=",vm.pusherClient()

            # and send a balance refresh on each reload
            sbAdmin.api.refreshBalances(id).then(
                (apiResponse)->
                    return
                , (errorResponse)->
                    console.log "ERROR: "+errorResponse.msg
                    return
            )

            return
        return vm

    sbAdmin.ctrl.botView.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        # bind unload event
        this.onunload = (e)->
            # console.log "unload bot view vm.pusherClient()=",vm.pusherClient()
            closePusherChannel(vm.pusherClient())
            return

        vm.init()
        return


    sbAdmin.ctrl.botView.view = ()->

        # console.log "vm.balances()=",vm.balances()

        mEl = m("div", [
                m("h2", "SwapBot #{vm.name()}"),

                m("div", {class: "spacer1"}),

                m("div", {class: "bot-view"}, [
                    sbAdmin.form.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [

                        m("div", {class: "col-md-8"}, [

                            m("div", { class: "row"}, [
                                m("div", {class: "col-md-3"}, [
                                    sbAdmin.form.mValueDisplay("Bot Name", {id: 'name',  }, vm.name()),
                                ]),
                                m("div", {class: "col-md-6"}, [
                                    sbAdmin.form.mValueDisplay("Address", {id: 'address',  }, if vm.address() then vm.address() else m("span", {class: 'no'}, "[ none ]")),
                                ]),
                                m("div", {class: "col-md-3"}, [
                                    sbAdmin.form.mValueDisplay("Status", {id: 'status',  }, if vm.active() then m("span", {class: 'yes'}, "Active") else m("span", {class: 'no'}, "Inactive")),
                                ]),
                            ]),

                            m("div", { class: "row"}, [
                                m("div", {class: "col-md-12"}, [
                                    sbAdmin.form.mValueDisplay("Bot Description", {id: 'description',  }, vm.description()),
                                ]),


                            ]),
                        ]),

                        # #### Balances
                        m("div", {class: "col-md-4"}, [
                            sbAdmin.form.mValueDisplay("Balances", {id: 'balances',  }, buildBalancesMElement(vm.balances())),
                        ]),
                    ]),


                    m("hr"),

                    vm.swaps().map (swap, offset)->
                        return swapGroup(offset+1, swap)


                    m("hr"),

                    m("div", {class: "bot-events"}, [
                        m("div", {class: "pulse-spinner pull-right"}, [m("div", {class: "rect1",}),m("div", {class: "rect2",}),m("div", {class: "rect3",}),m("div", {class: "rect4",}),m("div", {class: "rect5",}),]),
                        m("h3", "Events"),
                        m("ul", {class: "list-unstyled striped-list event-list"}, [
                            vm.botEvents().map (botEventObj)->
                                if not vm.showDebug and botEventObj.level <= 100
                                    return
                                dateObj = window.moment(botEventObj.createdAt)
                                return m("li", {class: "event"}, [
                                    m("div", {class: "labelWrapper"}, buildMLevel(botEventObj.level)),
                                    m("span", {class: "date", title: dateObj.format('MMMM Do YYYY, h:mm:ss a')}, dateObj.format('MMM D h:mm a')),
                                    m("span", {class: "msg"}, botEventObj.event?.msg),
                                ])
                        ]),
                        m("div", {class: "pull-right"}, [
                            m("a[href='#show-debug']", {onclick: vm.toggleDebugView, class: "btn #{if vm.showDebug then 'btn-warning' else 'btn-default'} btn-xs", style: {"margin-right": "16px",} }, [
                                if vm.showDebug then "Hide Debug" else "Show Debug"
                            ]),
                        ]),
                    ]),

                    m("div", {class: "spacer1"}),
                    m("hr"),

                    m("div", {class: "spacer2"}),

                    m("a[href='/admin/edit/bot/#{vm.resourceId()}']", {class: "btn btn-success", config: m.route}, "Edit This Bot"),
                    m("a[href='/admin/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Back to Dashboard"),
                    

                ]),


        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]

    sbAdmin.ctrl.botView.UnloadEvent