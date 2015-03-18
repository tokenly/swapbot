do ()->

    sbAdmin.ctrl.botForm = {}

    # ### helpers #####################################
    swapGroupRenderers = {}
    swapGroupRenderers.rate = (number, swap)->
        return m("div", {class: "asset-group"}, [
            m("h4", "Swap ##{number}"),
            m("div", { class: "row"}, [
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mFormField("Swap Type", {id: "swap_strategy_#{number}", type: 'select', options: sbAdmin.swaputils.allStrategyOptions()}, swap.strategy),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Receives Asset", {id: "swap_in_#{number}", 'placeholder': "BTC", }, swap.in),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Sends Asset", {id: "swap_out_#{number}", 'placeholder': "LTBCOIN", }, swap.out),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("At Rate", {type: "number", step: "any", min: "0", id: "swap_rate_#{number}", 'placeholder': "0.000001", }, swap.rate),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Minimum", {type: "number", step: "any", min: "0", id: "swap_rate_#{number}", 'placeholder': "0.000001", }, swap.min),
                ]),
                m("div", {class: "col-md-1"}, [
                    m("a", {class: "remove-link", href: '#remove', onclick: vm.buildRemoveSwapFn(number), style: if number == 1 then {display: 'none'} else ""}, [
                        m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Swap #{number}"}, ''),
                    ]),
                ]),
            ]),
        ])

    swapGroupRenderers.fixed = (number, swap)->
        return m("div", {class: "asset-group"}, [
            m("h4", "Swap ##{number}"),
            m("div", { class: "row"}, [
                m("div", {class: "col-md-3"}, [
                    sbAdmin.form.mFormField("Swap Type", {id: "swap_strategy_#{number}", type: 'select', options: sbAdmin.swaputils.allStrategyOptions()}, swap.strategy),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Receives Asset", {id: "swap_in_#{number}", 'placeholder': "BTC", }, swap.in),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Receives Quantity", {type: "number", step: "any", min: "0", id: "swap_in_qty_#{number}", 'placeholder': "1", }, swap.in_qty),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Sends Asset", {id: "swap_out_#{number}", 'placeholder': "LTBCOIN", }, swap.out),
                ]),
                m("div", {class: "col-md-2"}, [
                    sbAdmin.form.mFormField("Sends Quantity", {type: "number", step: "any", min: "0", id: "swap_out_qty_#{number}", 'placeholder': "1", }, swap.out_qty),
                ]),
                m("div", {class: "col-md-1"}, [
                    m("a", {class: "remove-link", href: '#remove', onclick: vm.buildRemoveSwapFn(number), style: if number == 1 then {display: 'none'} else ""}, [
                        m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Swap #{number}"}, ''),
                    ]),
                ]),
            ]),
        ])

    swapGroup = (number, swapProp)->
        return swapGroupRenderers[swapProp().strategy()](number, swapProp())

    # ################################################

    buildIncomeRulesGroup = ()->
        return sbAdmin.formGroup.newGroup({
            id: 'incomerules'
            fields: [
                {name: 'asset', }
                {name: 'minThreshold', }
                {name: 'paymentAmount', }
                {name: 'address', }
            ]
            addLabel: "Add Another Income Forwarding Rule"
            buildItemRow: (builder, number, item)->
                return [
                    builder.header("Income Forwarding Rule ##{number}"),
                    builder.row([
                        builder.field("Asset Received", 'asset', 'BTC', 3), 
                        builder.field("Trigger Threshold", 'minThreshold', {type: "number", step: "any", min: "0", placeholder: "1.0"}), 
                        builder.field("Payment Amount", 'paymentAmount', {type: "number", step: "any", min: "0", placeholder: "0.5"}), 
                        builder.field("Payment Address", 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4), 
                    ]),
                ]
        })

    # ################################################

    buildBlacklistAddressesGroup = ()->
        return sbAdmin.formGroup.newGroup({
            id: 'blacklist'
            fields: [
                {name: 'address', }
            ]
            addLabel: " Add Another Blacklist Address"
            buildItemRow: (builder, number, item)->
                return [
                    builder.row([
                        builder.field(null, 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4), 
                    ]),
                ]
            translateFieldToNumberedValues: 'address'
            useCompactNumberedLayout: true
        })

    # ################################################

    vm = sbAdmin.ctrl.botForm.vm = do ()->
        buildSwapsPropValue = (swaps)->
            out = []
            for swap in swaps
                out.push(sbAdmin.swaputils.newSwapProp(swap))

            # always have at least one
            if not out.length
                out.push(sbAdmin.swaputils.newSwapProp())

            return out


        buildBlacklistAddressesPropValue = (addresses)->
            out = []
            for address in addresses
                out.push(m.prop(address))

            # always have at least one
            if not out.length
                out.push(m.prop(''))

            return out

        vm = {}
        vm.init = ()->
            # view status
            vm.errorMessages = m.prop([])
            vm.formStatus = m.prop('active')
            vm.resourceId = m.prop('')

            # fields
            vm.name = m.prop('')
            vm.description = m.prop('')
            vm.paymentPlan = m.prop('')
            vm.returnFee = m.prop(0.0001)
            vm.confirmationsRequired = m.prop(2)
            vm.swaps = m.prop([sbAdmin.swaputils.newSwapProp()])
            
            vm.incomeRulesGroup = buildIncomeRulesGroup()
            vm.blacklistAddressesGroup = buildBlacklistAddressesGroup()

            # if there is an id, then load it from the api
            id = m.route.param('id')
            vm.isNew = (id == 'new')
            if !vm.isNew
                # load the bot info from the api
                sbAdmin.api.getBot(id).then(
                    (botData)->
                        vm.resourceId(botData.id)

                        vm.name(botData.name)
                        vm.description(botData.description)
                        vm.paymentPlan(botData.paymentPlan)
                        vm.swaps(buildSwapsPropValue(botData.swaps))
                        vm.returnFee(botData.returnFee or "0.0001")
                        vm.confirmationsRequired(botData.confirmationsRequired or "2")

                        vm.incomeRulesGroup.unserialize(botData.incomeRules)
                        vm.blacklistAddressesGroup.unserialize(botData.blacklistAddresses)

                        return
                    , (errorResponse)->
                        vm.errorMessages(errorResponse.errors)
                        return
                )

            vm.addSwap = (e)->
                e.preventDefault()
                vm.swaps().push(sbAdmin.swaputils.newSwapProp())
                return

            vm.buildRemoveSwapFn = (number)->
                return (e)->
                    e.preventDefault()

                    # filter newSwaps
                    newSwaps = vm.swaps().filter (swap, index)->
                        return (index != number - 1)
                    vm.swaps(newSwaps)
                    return


            vm.save = (e)->
                e.preventDefault()

                attributes = {
                    name: vm.name()
                    description: vm.description()
                    paymentPlan: vm.paymentPlan()
                    swaps: vm.swaps()
                    returnFee: vm.returnFee() + ""
                    incomeRules: vm.incomeRulesGroup.serialize()
                    blacklistAddresses: vm.blacklistAddressesGroup.serialize()
                    confirmationsRequired: vm.confirmationsRequired() + ""
                }

                if vm.resourceId().length > 0
                    # update existing bot
                    apiCall = sbAdmin.api.updateBot
                    apiArgs = [vm.resourceId(), attributes]
                else
                    # new bot
                    apiCall = sbAdmin.api.newBot
                    apiArgs = [attributes]

                sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(()->
                    console.log "submit complete - routing to dashboard"
                    # back to dashboard
                    m.route('/admin/dashboard')
                    return
                )

            return
        return vm

    sbAdmin.ctrl.botForm.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        vm.init()
        return

    sbAdmin.ctrl.botForm.view = ()->
        mEl = m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("h2", if vm.resourceId() then "Edit SwapBot #{vm.name()}" else "Create a New Swapbot"),

                    m("div", {class: "spacer1"}),

                    # m("form", {onsubmit: vm.save, }, [
                    sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                        sbAdmin.form.mAlerts(vm.errorMessages),

                        sbAdmin.form.mFormField("Bot Name", {id: 'name', 'placeholder': "Bot Name", required: true, }, vm.name),
                        sbAdmin.form.mFormField("Bot Description", {type: 'textarea', id: 'description', 'placeholder': "Bot Description", required: true, }, vm.description),


                        m("hr"),

                        m("h4", "Settings"),

                        # return fee
                        m("div", {class: "spacer1"}),
                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-5"}, [
                                sbAdmin.form.mFormField("Confirmations", {id: 'confirmations_required', 'placeholder': "2", type: "number", step: "1", min: "1", required: true, }, vm.confirmationsRequired),
                            ]),
                            m("div", {class: "col-md-5"}, [
                                sbAdmin.form.mFormField("Return Transaction Fee", {id: 'return_fee', 'placeholder': "0.0001", type: "number", step: "any", min: "0.00001", required: true, }, vm.returnFee),
                            ]),
                        ]),

                        m("h5", "Blacklisted Addresses"),
                        m("p", [m("small", "Blacklisted addresses do not trigger swaps and can be used to load the SwapBot.")]),
                        vm.blacklistAddressesGroup.buildInputs(),

                        m("hr"),

                        # overflow/income address
                        m("h4", "Income Forwarding"),
                        m("p", [m("small", "When the bot fills up to a certain amount, you may forward the funds to your own destination address.")]),
                        vm.incomeRulesGroup.buildInputs(),


                        m("hr"),

                        m("h4", "Payment"),
                        # m("p", [m("small", "Choose a payment plan.")]),
                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-12"}, [
                                (if vm.isNew then sbAdmin.form.mFormField("Payment Plan", {id: "payment_plan", type: 'select', options: sbAdmin.planutils.allPlanOptions()}, vm.paymentPlan) else null),
                                (if not vm.isNew then sbAdmin.form.mValueDisplay("Payment Plan", {id: 'payment_plan',  }, sbAdmin.planutils.paymentPlanDesc(vm.paymentPlan())) else null),
                            ]),
                        ]),

                        m("hr"),

                        vm.swaps().map((swap, offset)->
                            return swapGroup(offset+1, swap)
                        ),

                        # add asset
                        m("div", {class: "form-group"}, [
                                m("a", {class: "", href: '#add', onclick: vm.addSwap}, [
                                    m("span", {class: "glyphicon glyphicon-plus"}, ''),
                                    m("span", {}, ' Add Another Swap'),
                                ]),
                        ]),


                        m("div", {class: "spacer1"}),

                        sbAdmin.form.mSubmitBtn("Save Bot"),
                        m("a[href='/admin/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                        

                    ]),

                ]),
            ]),



        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


