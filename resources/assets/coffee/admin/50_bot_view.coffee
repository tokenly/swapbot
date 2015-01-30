do ()->

    sbAdmin.ctrl.botView = {}

    # ### helpers #####################################
    swapGroup = (number, swapProp)->

        return m("div", {class: "asset-group"}, [
            m("h4", "Swap ##{number}"),
            m("div", { class: "row"}, [
                m("div", {class: "col-md-4"}, [
                    sbAdmin.form.mValueDisplay("Receives Asset", {id: "swap_in_#{number}", }, swapProp().in()),
                ]),
                m("div", {class: "col-md-4"}, [
                    sbAdmin.form.mValueDisplay("Sends Asset", {id: "swap_out_#{number}", }, swapProp().out()),
                ]),
                m("div", {class: "col-md-4"}, [
                    sbAdmin.form.mValueDisplay("Rate", {type: "number", step: "any", min: "0", id: "swap_rate_#{number}", }, swapProp().rate()),
                ]),
            ]),
        ])

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



    handlePusherMessage = (data)->
        console.log "pusher says:", data
        vm.botEvents().unshift(data)
        # this is outside of mithril, so we must force a redraw
        m.redraw(true)
        return


    # ################################################

    vm = sbAdmin.ctrl.botView.vm = do ()->
        buildSwapsPropValue = (swaps)->
            out = []
            for swap in swaps
                out.push(newSwapProp(swap))
            return out

        newSwapProp = (swap={})->
            return m.prop({
                in: m.prop(swap.in or '')
                out: m.prop(swap.out or '')
                rate: m.prop(swap.rate or '')
            })

        vm = {}
        vm.init = ()->
            # view status
            vm.errorMessages = m.prop([])
            vm.formStatus = m.prop('active')
            vm.resourceId = m.prop('new')
            vm.pusherClient = m.prop(null)
            vm.botEvents = m.prop([])

            # fields
            vm.name = m.prop('')
            vm.description = m.prop('')
            vm.address = m.prop('')
            vm.active = m.prop('')
            vm.swaps = m.prop(buildSwapsPropValue([]))

            # if there is an id, then load it from the api
            id = m.route.param('id')
            # load the bot info from the api
            sbAdmin.api.getBot(id).then(
                (botData)->
                    console.log "botData", botData
                    vm.resourceId(botData.id)

                    vm.name(botData.name)
                    vm.address(botData.address)
                    vm.active(botData.active)
                    vm.description(botData.description)
                    vm.swaps(buildSwapsPropValue(botData.swaps))

                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )

            vm.pusherClient(subscribeToPusherChannel("swapbot_#{id}", handlePusherMessage))
            console.log "vm.pusherClient=",vm.pusherClient()

            return
        return vm

    sbAdmin.ctrl.botView.controller = ()->
        # bind unload event
        this.onunload = (e)->
            console.log "unload bot view vm.pusherClient()=",vm.pusherClient()
            closePusherChannel(vm.pusherClient())
            return

        vm.init()
        return


    sbAdmin.ctrl.botView.view = ()->
        return m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("h2", "SwapBot #{vm.name()}"),

                    m("div", {class: "spacer1"}),

                    m("div", {class: "bot-view"}, [
                        sbAdmin.form.mAlerts(vm.errorMessages),


                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.form.mValueDisplay("Bot Name", {id: 'name',  }, vm.name()),
                            ]),
                            m("div", {class: "col-md-6"}, [
                                sbAdmin.form.mValueDisplay("Address", {id: 'address',  }, if vm.address() then vm.address() else m("span", {class: 'no'}, "[ none ]")),
                            ]),
                            m("div", {class: "col-md-2"}, [
                                sbAdmin.form.mValueDisplay("Status", {id: 'status',  }, if vm.active() then m("span", {class: 'yes'}, "Active") else m("span", {class: 'no'}, "Inactive")),
                            ]),
                        ]),

                        sbAdmin.form.mValueDisplay("Bot Description", {id: 'description',  }, vm.description()),

                        m("hr"),

                        vm.swaps().map (swap, offset)->
                            return swapGroup(offset+1, swap)


                        m("hr"),

                        m("div", {class: "bot-events"}, [
                            m("h3", "Events"),
                            vm.botEvents().map (event)->
                                return m("div", {class: "event"}, event)
                        ]),


                        m("div", {class: "spacer2"}),

                        m("a[href='/edit/bot/#{vm.resourceId()}']", {class: "btn btn-success", config: m.route}, "Edit This Bot"),
                        m("a[href='/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Back to Dashboard"),
                        

                    ]),

                ]),
            ]),



        ])

    sbAdmin.ctrl.botView.UnloadEvent