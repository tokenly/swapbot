# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.constants = require './05_constants'
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.botPaymentUtils = require './10_bot_payment_utils'
sbAdmin = sbAdmin or {}; sbAdmin.botutils = require './10_bot_utils'
sbAdmin = sbAdmin or {}; sbAdmin.fileHelper = require './10_file_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.formGroup = require './10_form_group'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.planutils = require './10_plan_utils'
sbAdmin = sbAdmin or {}; sbAdmin.pusherutils = require './10_pusher_utils'
sbAdmin = sbAdmin or {}; sbAdmin.quotebotSubscriber = require './10_quotebot_subscriber'
sbAdmin = sbAdmin or {}; sbAdmin.robohashUtils = require './10_robohash_utils'
sbAdmin = sbAdmin or {}; sbAdmin.stateutils = require './10_state_utils'
sbAdmin = sbAdmin or {}; sbAdmin.swaputils = require './10_swap_utils'
sbAdmin = sbAdmin or {}; sbAdmin.utils = require './10_utils'
sbAdmin = sbAdmin or {}; sbAdmin.swapgrouprenderer = require './10_swap_form_group_renderer'
swapbot = swapbot or {}; swapbot.addressUtils = require '../shared/addressUtils'
# ---- end references

ctrl = {}

ctrl.botView = {}

constants = sbAdmin.constants


# ### helpers #####################################

buildIncomeRulesGroup = ()->
    return sbAdmin.formGroup.newGroup({
        id: 'incomerules'
        fields: [
            {name: 'asset', }
            {name: 'minThreshold', }
            {name: 'paymentAmount', }
            {name: 'address', }
        ]
        buildAllItemRows: (items)->
            if not items or (items.length == 1 and not items[0].asset())
                return [
                    m("h4", "Income Forwarding Rules"),
                    m("span", {class: 'empty'}, "No income forwarding rules are defined."),
                ]
            return null
        buildItemRow: (builder, number, item)->
            return [
                builder.header("Income Forwarding Rule ##{number}"),
                builder.row([
                    builder.value("Asset Received", 'asset', {}, 3), 
                    builder.value("Trigger Threshold", 'minThreshold', {}), 
                    builder.value("Payment Amount", 'paymentAmount', {}), 
                    builder.value("Payment Address", 'address', {}, 4), 
                ]),
            ]
        displayOnly: true
    })

# ################################################

buildBlacklistAddressesGroup = ()->
    return sbAdmin.formGroup.newGroup({
        id: 'blacklist'
        fields: [
            {name: 'address', }
        ]
        buildAllItemRows: (items)->
            if not items or (items.length == 1 and not items[0].address())
                return [
                    m("span", {class: 'empty'}, "No blacklisted addresses are defined."),
                ]

            addressList = ""
            for item, offset in items
                addressList += (if offset > 0 then ", " else "")+item.address()

            return m("div", {class: "item-group"}, [
                m("div", { class: "row"}, m("div", {class: "col-md-12 form-control-static"}, addressList)),
            ])

            
        translateFieldToNumberedValues: 'address'
        useCompactNumberedLayout: true
        displayOnly: true
    })

# ################################################

botPublicAddress = (vm)->
    return swapbot.addressUtils.publicBotHref(vm.username(), vm.urlSlug(), vm.resourceId(), window.location)

# ################################################


handleBotEventMessage = (data)->
    # console.log "pusher received:", data
    # console.log "msg:", data?.event?.msg
    if data?.event?.msg or data?.message
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

curryHandleAccountUpdatesMessage = (id)->
    return (data)->
        updateBotAccountBalance(id)
        return

updateBotAccountBalance = (id)->
    sbAdmin.api.getBotPaymentBalances(id).then(
        (apiResponse)->
            paymentBalances = []
            for asset, val of apiResponse.balances
                paymentBalances.push({asset: asset, val: val})
            vm.paymentBalances(paymentBalances)
            m.redraw(true)
            return
        , (errorResponse)->
            vm.errorMessages(errorResponse.errors)
            return
    )

    sbAdmin.api.getAllBotPayments(id).then(
        (apiResponse)->
            apiResponse.reverse()
            vm.payments(apiResponse)
            vm.paymentsSet(true)
            m.redraw(true)
            return
        , (errorResponse)->
            vm.errorMessages(errorResponse.errors)
            return
    )

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





# ################################################


# ################################################

vm = ctrl.botView.vm = do ()->
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
        vm.pusherClients = []
        vm.quotebotSubscriberID = null
        vm.btcQuote = m.prop(null)
        vm.showDebug = false

        vm.errorMessages = m.prop([])
        vm.formStatus = m.prop('active')
        vm.resourceId = m.prop('new')
        vm.botEvents = m.prop([])
        vm.allPlansData = m.prop(null)

        # fields
        vm.name = m.prop('')
        vm.description = m.prop('')
        vm.hash = m.prop('')
        vm.username = m.prop('')
        vm.urlSlug = m.prop('')
        vm.address = m.prop('')
        vm.paymentAddress = m.prop('')
        vm.paymentPlan = m.prop('')
        vm.state = m.prop('')
        vm.swaps = m.prop(buildSwapsPropValue([]))
        vm.balances = m.prop(buildBalancesPropValue([]))
        vm.confirmationsRequired = m.prop('')
        vm.returnFee = m.prop('')
        vm.refundAfterBlocks = m.prop('')
        vm.paymentBalances = m.prop('')
        vm.payments = m.prop([])
        vm.paymentsSet = m.prop(false)

        vm.incomeRulesGroup = buildIncomeRulesGroup()
        vm.blacklistAddressesGroup = buildBlacklistAddressesGroup()

        vm.backgroundImageDetails = m.prop('')
        vm.logoImageDetails       = m.prop('')
        vm.backgroundOverlaySettings = m.prop('')

        # payment calculator
        vm.paymentAssetType = m.prop('')
        vm.paymentMonths = m.prop('')

        # if there is an id, then load it from the api
        id = m.route.param('id')
        # load the bot info from the api
        sbAdmin.api.getBot(id).then(
            (botData)->
                # console.log "botData", botData
                vm.resourceId(botData.id)

                vm.name(botData.name)
                vm.address(botData.address)
                vm.paymentAddress(botData.paymentAddress)
                vm.paymentPlan(botData.paymentPlan)
                vm.state(botData.state)
                # vm.description(botData.description)
                vm.description(botData.descriptionHtml)
                vm.hash(botData.hash)
                vm.username(botData.username)
                vm.urlSlug(botData.urlSlug)
                vm.swaps(buildSwapsPropValue(botData.swaps))
                vm.balances(buildBalancesPropValue(botData.balances))
                vm.confirmationsRequired(botData.confirmationsRequired)
                vm.returnFee(botData.returnFee)

                vm.incomeRulesGroup.unserialize(botData.incomeRules)
                vm.blacklistAddressesGroup.unserialize(botData.blacklistAddresses)

                vm.backgroundImageDetails(botData.backgroundImageDetails)
                vm.logoImageDetails(botData.logoImageDetails)
                vm.backgroundOverlaySettings(botData.backgroundOverlaySettings)

                vm.refundAfterBlocks(botData.refundConfig?.refundAfterBlocks)

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

        # and the plan options
        sbAdmin.api.getAllPlansData().then(
            (apiResponse)->
                vm.allPlansData(apiResponse)
                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )

        # and get the bot balance
        updateBotAccountBalance(id)

        handleQuotebotUpdate = (btcUSDValue)->
            vm.btcQuote(btcUSDValue)
            m.redraw(true)
            return

        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_#{id}", handleBotEventMessage))
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_balances_#{id}", handleBotBalancesMessage))
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_account_updates_#{id}", curryHandleAccountUpdatesMessage(id)))
        vm.quotebotSubscriberID = sbAdmin.quotebotSubscriber.addChangeListener(handleQuotebotUpdate)

        # refresh balances are not needed
        # # and send a balance refresh on each reload
        # sbAdmin.api.refreshBalances(id).then(
        #     (apiResponse)->
        #         return
        #     , (errorResponse)->
        #         console.log "ERROR: "+errorResponse.msg
        #         return
        # )

        return
    return vm

ctrl.botView.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # bind unload event
    this.onunload = (e)->
        for pusherClient in vm.pusherClients
            sbAdmin.pusherutils.closePusherChanel(pusherClient)
        sbAdmin.quotebotSubscriber.removeChangeListener(vm.quotebotSubscriberID)
        return

    vm.init()
    return


ctrl.botView.view = ()->
    botPaymentUtils = sbAdmin.botPaymentUtils

    # console.log "vm.balances()=",vm.balances()

    mEl = m("div", [


            m("div", { class: "row"}, [
                m("div", {class: "col-md-10"}, [
                    m("h2", "SwapBot #{vm.name()}"),
                ]),
                m("div", {class: "col-md-2 text-right"}, [
                    sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead'),
                ]),
            ]),


            

            m("div", {class: "spacer1"}),

            m("div", {class: "bot-status"}, [
                sbAdmin.stateutils.buildStateDisplay(sbAdmin.stateutils.buildStateDetails(vm.state(), sbAdmin.planutils.planData(vm.paymentPlan(), vm.allPlansData()), vm.paymentAddress(), vm.address()))
            ]),

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
                                sbAdmin.form.mValueDisplay("Bot Address", {id: 'address',  }, if vm.address() then vm.address() else m("span", {class: 'no'}, "[ none ]")),
                            ]),
                            m("div", {class: "col-md-3"}, [
                                sbAdmin.form.mValueDisplay("Status", {id: 'status',  }, sbAdmin.stateutils.buildStateSpan(vm.state())),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-3"}, [
                                sbAdmin.form.mValueDisplay("Return Fee", {id: 'return_fee',  }, vm.returnFee()+' BTC'),
                            ]),
                            m("div", {class: "col-md-3"}, [
                                sbAdmin.form.mValueDisplay("Confirmations", {id: 'confirmations_required', }, vm.confirmationsRequired()),
                            ]),
                            m("div", {class: "col-md-6"}, [
                                sbAdmin.form.mValueDisplay("Refund Out of Stock Swaps After", {id: 'refund_after_blocks', }, vm.refundAfterBlocks()+" blocks"),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-12"}, [
                                sbAdmin.form.mValueDisplay("Bot Description", {id: 'description',  }, m.trust(vm.description())),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-7"}, [
                                sbAdmin.fileHelper.mImageDisplay("Custom Background Image", {id: 'BGImage'}, vm.backgroundImageDetails, 'medium'),
                            ]),
                            m("div", {class: "col-md-5"}, [
                                sbAdmin.fileHelper.mImageDisplay("Custom Logo Image", {id: 'LogoImage'}, vm.logoImageDetails, 'thumb'),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-7"}, [
                                
                                sbAdmin.form.mValueDisplay("Background Overlay", {id: 'BackgroundOverlay',  }, sbAdmin.botutils.overlayDesc(vm.backgroundOverlaySettings())),
                            ]),
                        ]),



                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-12"}, [
                                sbAdmin.form.mValueDisplay("Public Bot Address", {id: 'description',  }, [
                                    m("a", {href: botPublicAddress(vm)}, botPublicAddress(vm))
                                ]),
                            ]),
                        ]),
                    ]),

                    # #### Balances
                    m("div", {class: "col-md-4"}, [
                        sbAdmin.form.mValueDisplay("Bot Balances", {id: 'balances',  }, sbAdmin.utils.buildBalancesMElement(vm.balances())),
                    ]),
                ]),


                # -------------------------------------------------------------------------------------------------------------------------------------------
                m("div", {class: "spacer1"}),
                m("hr"),

                m("h3", "Swaps Selling Tokens"),
                sbAdmin.swapgrouprenderer.buildSwapsSectionForDisplay(constants.DIRECTION_SELL, vm.swaps()),
                m("div", {class: "spacer1"}),
                m("h3", "Swaps Purchasing Tokens"),
                sbAdmin.swapgrouprenderer.buildSwapsSectionForDisplay(constants.DIRECTION_BUY, vm.swaps()),


                # -------------------------------------------------------------------------------------------------------------------------------------------
                m("div", {class: "spacer1"}),
                m("hr"),

                # overflow/income address
                vm.incomeRulesGroup.buildValues(),


                # -------------------------------------------------------------------------------------------------------------------------------------------
                m("div", {class: "spacer1"}),
                m("hr"),
                m("h4", "Blacklisted Addresses"),
                vm.blacklistAddressesGroup.buildValues(),


                # -------------------------------------------------------------------------------------------------------------------------------------------
                m("div", {class: "spacer1"}),
                m("hr"),

                m("div", {class: "bot-payments"}, [
                    m("h3", "Payment"),
                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-9"}, [
                            m("div", { class: "row"}, [
                                m("div", {class: "col-md-3"}, [
                                    sbAdmin.form.mValueDisplay("Next Payment Due", {id: 'due-date',  }, if vm.paymentsSet() then botPaymentUtils.buildFormattedBotDueDateText(vm.payments(), vm.paymentBalances()) else 'loading...'),
                                ]),
                                m("div", {class: "col-md-3"}, [
                                    sbAdmin.form.mValueDisplay("Payment Plan", {id: 'rate',  }, sbAdmin.planutils.paymentPlanDesc(vm.paymentPlan(), vm.allPlansData())),
                                ]),
                                m("div", {class: "col-md-6"}, [
                                    sbAdmin.form.mValueDisplay("Payment Address", {id: 'paymentAddress',  }, vm.paymentAddress()),
                                ]),
                            ]),
                    
                            m("div", { class: "row"}, [
                                m("div", {class: "col-md-3"}, [
                                    botPaymentUtils.buildMakePaymentPulldown(vm.paymentAssetType, vm.allPlansData, vm.btcQuote)
                                ]),
                                m("div", {class: "col-md-3"}, [
                                    botPaymentUtils.buildMonthsPaymentPulldown(vm.paymentMonths)
                                ]),
                                m("div", {class: "col-md-6"}, [
                                    botPaymentUtils.buildPayHereDisplay(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.allPlansData, vm.btcQuote)
                                ]),
                            ]),

                            m("div", { class: "row"}, [
                                m("div", {class: "col-md-12"}, [
                                    botPaymentUtils.buildReceivingPayment(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.botEvents, vm.allPlansData, vm.btcQuote)
                                ]),
                            ]),

                        ]),
                        m("div", {class: "col-md-3"}, [
                            sbAdmin.form.mValueDisplay("Payment Account Balances", {id: 'balances',  }, sbAdmin.utils.buildBalancesMElement(vm.paymentBalances())),
                        ]),
                    ]),
                    
                ]),

                m("div", {class: "spacer1"}),
                
                m("a[href='/admin/payments/bot/#{vm.resourceId()}']", {class: "btn btn-info", config: m.route}, "View Payment History"),

                m("div", {class: "spacer1"}),

                m("hr"),
                m("div", {class: "bot-events"}, [
                    m("div", {class: "pulse-spinner pull-right"}, [m("div", {class: "rect1",}),m("div", {class: "rect2",}),m("div", {class: "rect3",}),m("div", {class: "rect4",}),m("div", {class: "rect5",}),]),
                    m("h3", "Events"),
                    if vm.botEvents().length == 0 then m("div", {class:"empty no-events", }, "No Events Yet") else null,
                    m("ul", {class: "list-unstyled striped-list bot-list event-list"+(if vm.showDebug then ' event-list-debug' else '')}, [
                        vm.botEvents().map (botEventObj)->
                            if not vm.showDebug and botEventObj.level <= 100
                                return
                            dateObj = window.moment(botEventObj.createdAt)
                            return m("li", {class: "bot-list-entry event"}, [
                                m("div", {class: "labelWrapper"}, buildMLevel(botEventObj.level)),
                                m("span", {class: "date", title: dateObj.format('MMMM Do YYYY, h:mm:ss a')}, dateObj.format('MMM D h:mm a')),
                                m("span", {class: "msg"}, (botEventObj.message or botEventObj.event?.msg)),
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

                m("a[href='/admin/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Back to Dashboard"),

                (
                    if vm.username() == sbAdmin.auth.getUser().username or sbAdmin.auth.hasPermssion('editBots')
                        m("a[href='/admin/edit/bot/#{vm.resourceId()}']", {class: "btn btn-success", config: m.route}, "Edit This Bot")
                    else
                        null
                ),


            ]),


    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]

######
module.exports = ctrl.botView
