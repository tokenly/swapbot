# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.botPaymentUtils = require './10_bot_payment_utils'
sbAdmin = sbAdmin or {}; sbAdmin.currencyutils = require './10_currency_utils'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.planutils = require './10_plan_utils'
sbAdmin = sbAdmin or {}; sbAdmin.pusherutils = require './10_pusher_utils'
sbAdmin = sbAdmin or {}; sbAdmin.quotebotSubscriber = require './10_quotebot_subscriber'
sbAdmin = sbAdmin or {}; sbAdmin.utils = require './10_utils'
# ---- end references

ctrl = {}

ctrl.botPaymentsView = {}


# ### helpers #####################################


curryHandleAccountUpdatesMessage = (id)->
    return (data)->
        updateAllAccountPayments(id)
        return

updateAllAccountPayments = (id)->
    sbAdmin.api.getBotPaymentBalances(id).then(
        (apiResponse)->
            paymentBalances = []
            for asset, val of apiResponse.balances
                paymentBalances.push({asset: asset, val: val})
            vm.paymentBalances(paymentBalances)
            return
        , (errorResponse)->
            vm.errorMessages(errorResponse.errors)
            return
    )

    sbAdmin.api.getAllBotPayments(id).then(
        (apiResponse)->
            apiResponse.reverse()
            vm.payments(apiResponse)
            return
        , (errorResponse)->
            vm.errorMessages(errorResponse.errors)
            return
    )

    return

buildPaymentTypeLabel = (isCredit)->
    if isCredit
        return m('span', {class: "label label-success"}, "Credit")
    else
        return m('span', {class: "label label-warning"}, "Debit")


handleBotEventMessage = (data)->
    if data?.event?.msg or data?.message
        vm.botEvents().unshift(data)
        # this is outside of mithril, so we must force a redraw
        m.redraw(true)
    return

# ################################################

vm = ctrl.botPaymentsView.vm = do ()->

    vm = {}


    vm.init = ()->
        # view status
        vm.errorMessages = m.prop([])
        vm.resourceId = m.prop('')
        vm.pusherClients = []
        vm.quotebotSubscriberID = null
        vm.btcQuote = m.prop(null)
        vm.botEvents = m.prop([])
        vm.allPlansData = m.prop(null)

        # fields
        vm.name = m.prop('')
        vm.address = m.prop('')
        vm.paymentAddress = m.prop('')
        vm.paymentPlan = m.prop('')
        vm.state = m.prop('')
        vm.paymentBalances = m.prop('')
        vm.payments = m.prop([])

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

        # also get the bot events
        sbAdmin.api.getBotEvents(id).then(
            (apiResponse)->
                vm.botEvents(apiResponse)
                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )

        handleQuotebotUpdate = (btcUSDValue)->
            vm.btcQuote(btcUSDValue)
            m.redraw(true)
            return


        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_#{id}", handleBotEventMessage))
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_account_updates_#{id}", curryHandleAccountUpdatesMessage(id)))
        vm.quotebotSubscriberID = sbAdmin.quotebotSubscriber.addChangeListener(handleQuotebotUpdate)
        updateAllAccountPayments(id)

        return
    return vm

ctrl.botPaymentsView.controller = ()->
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


ctrl.botPaymentsView.view = ()->

    # console.log "vm.balances()=",vm.balances()
    # console.log "vm.payments().length=",vm.payments().length

    mEl = m("div", [
            m("h2", "SwapBot #{vm.name()}"),

            m("div", {class: "spacer1"}),

            m("div", {class: "bot-payments-view"}, [
                sbAdmin.form.mAlerts(vm.errorMessages),

    
                m("h3", "Payment Status"),
                m("div", { class: "row"}, [
                    m("div", {class: "col-md-9"}, [
                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-3"}, [
                                sbAdmin.form.mValueDisplay("Next Payment Due", {id: 'due-date',  }, sbAdmin.botPaymentUtils.buildFormattedBotDueDateText(vm.payments(), vm.paymentBalances())),
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
                        sbAdmin.form.mValueDisplay("Account Balances", {id: 'balances',  }, sbAdmin.utils.buildBalancesMElement(vm.paymentBalances())),
                    ]),
                ]),
            

                m("div", {class: "bot-payments"}, [
                    m("small", {class: "pull-right"}, "newest first"),
                    m("h3", "Payment History"),
                    if vm.payments().length == 0 then m("div", {class:"no-payments", }, "No Payments Yet") else null,
                    m("ul", {class: "list-unstyled striped-list bot-list payment-list"}, [
                        vm.payments().map (botPaymentObj)->
                            dateObj = window.moment(botPaymentObj.createdAt)
                            return m("li", {class: "bot-list-entry payment"}, [
                                m("div", {class: "labelWrapper"}, buildPaymentTypeLabel(botPaymentObj.isCredit)),
                                m("span", {class: "date", title: dateObj.format('MMMM Do YYYY, h:mm:ss a')}, dateObj.format('MMM D h:mm a')),
                                m("span", {class: "amount"}, sbAdmin.currencyutils.satoshisToValue(botPaymentObj.amount, botPaymentObj.asset)),
                                m("span", {class: "msg"}, botPaymentObj.msg),
                            ])
                    ]),
                ]),



                m("div", {class: "spacer2"}),

                m("a[href='/admin/view/bot/#{vm.resourceId()}']", {class: "btn btn-default", config: m.route}, "Return to Bot View"),
                

            ]),


    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]

######
module.exports = ctrl.botPaymentsView
