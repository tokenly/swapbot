# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.currencyutils = require './10_currency_utils'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.pocketsUtils = require './10_pockets_utils'
SwapbotAPI = require './10_api_functions'
# ---- end references

# botPaymentUtils functions

botPaymentUtils = {}


# ------------------------------------------------------------------------
# local functions

paymentPrices = (allPlansData, btcUSDValue)->
    prices = {}
    if allPlansData?.monthly001?
        for id, planData of allPlansData.monthly001.monthlyRates
            key = planData.asset
            qty = planData.quantity
            if planData.fiatAmount?
                qty = planData.fiatAmount / btcUSDValue
                qty = qty * 1.005 # 1% market buffer
                # console.log "planData.fiatAmount=#{planData.fiatAmount} btcUSDValue=#{btcUSDValue} qty=#{qty}"
            prices[key] = qty
    return prices

paymentOpts = (allPlansData, btcUSDValue)->
    opts = [{k: '- Choose One -', v: ''},]
    for asset, quantity of paymentPrices(allPlansData, btcUSDValue)
        if quantity?
            opts.push({k: sbAdmin.currencyutils.formatValue(quantity, asset)+assetPostfix(quantity, asset, btcUSDValue), v: asset})
    return opts

assetPostfix = (qty, asset, btcUSDValue)->
    if asset != 'BTC'
        return ''
    return " (#{sbAdmin.currencyutils.formatFiatCurrency(qty * btcUSDValue)})"

monthOpts = ()->
    monthOptions = [{k: '- Choose One -', v: ''},]
    for i in [1..36]
        monthOptions.push({k: i+' Month'+(if i == 1 then '' else 's'), v: i})
    return monthOptions

buildPocketsBtn = (address, monthsText, quantity, asset)->
    return sbAdmin.pocketsUtils.buildPaymentButton(address, "Swapbot Payment for #{monthsText}", quantity, asset)

buildPaymentDetails = (asset, months, allPlansData, btcUSDValue)->
    monthsText = months + ' month'+(if months > 1 then 's' else '')
    price = paymentPrices(allPlansData, btcUSDValue)[asset]
    quantity = months * price
    return [monthsText, quantity]

buildPaymentStatusAndDetails = (botEventsProp)->
    status = 'watching'
    details = null

    botEvents = botEventsProp().slice(0)
    botEvents.reverse()
    for botEvent in botEvents
        event = botEvent.event

        if event.name == 'payment.unconfirmed' or event.name == 'payment.confirmed'
            isOld = true
            if moment(botEvent.createdAt).add(2, 'minutes').isAfter(moment())
                isOld = false
            # console.log "#{event.name}: botEvent.createdAt=#{botEvent.createdAt} isOld=",isOld," botEvent=",botEvent
            if isOld then continue

            if event.name == 'payment.unconfirmed'
                status = 'unconfirmed'
            else if event.name == 'payment.confirmed'
                status = 'confirmed'
            else
                continue

            details = {txid: event.txid, source: event.source, inAsset: event.inAsset, inQty: event.inQty}

    return [status, details]
    

# ------------------------------------------------------------------------


botPaymentUtils.buildFormattedBotDueDateTextFromBot = (bot, paymentStatuses)->
    # only load once
    if paymentStatuses[bot.id]?.loaded
        return

    paymentStatuses[bot.id] = {}
    paymentStatuses[bot.id].loaded = true
    paymentStatuses[bot.id].resultText = m.prop(null)

    loadedPaymentBalances = null
    loadedPayments = null

    SwapbotAPI.getBotPaymentBalances(bot.id).then(
        (apiResponse)->
            loadedPaymentBalances = []
            for asset, val of apiResponse.balances
                loadedPaymentBalances.push({asset: asset, val: val})
            finish()
            return
        , (errorResponse)->
            console.error(errorResponse.errors)
            return
    )

    SwapbotAPI.getAllBotPayments(bot.id).then(
        (apiResponse)->
            apiResponse.reverse()
            loadedPayments = apiResponse
            finish()
            return
        , (errorResponse)->
            console.error(errorResponse.errors)
            return
    )

    finish = ()->
        if not loadedPayments? or not loadedPaymentBalances?
            return
        paymentStatuses[bot.id].resultText(botPaymentUtils.buildFormattedBotDueDateText(loadedPayments, loadedPaymentBalances))
        return

    return

botPaymentUtils.buildFormattedBotDueDateText = (payments, balances)->
    dueDate = botPaymentUtils.buildBotDueDate(payments, balances)
    if not dueDate?
        return m('span', {class: "label label-default label-big"}, 'Pending')

    now = moment()
    formattedDate = dueDate.format('MMM D YYYY, h:mm a')
    if dueDate.isBefore(moment())
        return m('span', {class: "label label-danger label-big"}, 'Past Due')
    if dueDate.isBefore(moment().add(1, 'week'))
        return ['', m('span', {class: "label label-danger label-big"}, formattedDate)]
    if dueDate.isBefore(moment().add(1, 'month'))
        return ['', m('span', {class: "label label-warning label-big"}, formattedDate)]
    if dueDate.isBefore(moment().add(2, 'months'))
        return ['', m('span', {class: "label label-primary label-big"}, formattedDate)]

    return ['', m('span', {class: "label label-success label-big"}, formattedDate)]
        
botPaymentUtils.buildBotDueDate = (payments, balances)->
    if not payments? or payments.length == 0
        return null

    lastPayment = null
    payments.map (botPaymentObj)->
        if not botPaymentObj.isCredit and botPaymentObj.asset == 'SWAPBOTMONTH'
            dateObj = window.moment(botPaymentObj.createdAt)
            if lastPayment?
                if dateObj.diff(lastPayment) > 0
                    lastPayment = dateObj
            else
                lastPayment = dateObj

    if not lastPayment?
        return null

    # for each unspent SWAPBOTMONTH token, add a month
    swapbotMonthBalance = 0
    if balances? and balances.length > 0
        balances.map (balanceEntry)->
            asset = balanceEntry.asset
            quantity = balanceEntry.val
            if asset == 'SWAPBOTMONTH'
                swapbotMonthBalance = quantity

    monthsToAdd = 1 + swapbotMonthBalance
    dueDate = lastPayment.clone().add(monthsToAdd, 'months')

    return dueDate

botPaymentUtils.buildMakePaymentPulldown = (paymentAssetProp, allPlansDataProp, btcUSDValueProp)->
    paymentOptions = paymentOpts(allPlansDataProp(), btcUSDValueProp())
    return sbAdmin.form.mFormField("Make a Payment With", {type: "select", options: paymentOptions, id: "payment-options",}, paymentAssetProp)

botPaymentUtils.buildMonthsPaymentPulldown = (monthsProp)->
    monthOptions = monthOpts()
    return sbAdmin.form.mFormField("For How Many Months", {type: "select", options: monthOptions, id: "payment-months",}, monthsProp)

botPaymentUtils.buildPayHereDisplay = (paymentAssetProp, monthsProp, addressProp, allPlansDataProp, btcUSDValueProp)->
    asset = paymentAssetProp()
    months = monthsProp()

    if not asset or not months
        return null

    [monthsText, quantity] = buildPaymentDetails(asset, months, allPlansDataProp(), btcUSDValueProp())
    paymentButton = buildPocketsBtn(addressProp(), monthsText, quantity, asset)
    totalValue = m('span', {class: 'payment-total'}, "#{sbAdmin.currencyutils.formatValue(quantity, asset)}#{assetPostfix(quantity, asset, btcUSDValueProp())} ")
    return sbAdmin.form.mValueDisplay("Your Total", {id: "payment-total",  }, [totalValue, ' ', paymentButton])

botPaymentUtils.buildReceivingPayment = (paymentAssetProp, monthsProp, addressProp, botEventsProp, allPlansDataProp, btcUSDValueProp)->
    asset = paymentAssetProp()
    months = monthsProp()

    if not asset or not months
        return null

    [status, paymentDetails] = buildPaymentStatusAndDetails(botEventsProp)
    # console.log "status=#{status}"

    if status == 'watching'
        [monthsText, quantity] = buildPaymentDetails(asset, months, allPlansDataProp(), btcUSDValueProp())
        paymentButton = buildPocketsBtn(addressProp(), monthsText, quantity, asset)
        msg = ["Watching for payment to #{addressProp()} ",paymentButton]

    if status == 'unconfirmed' or status == 'confirmed'
        href = "https://chain.so/tx/BTC/#{paymentDetails.txid}"
        msg = ["Received #{if status == 'unconfirmed' then 'an' else 'a'} ", m('a', {href: href, target: "_blank"}, status), " payment of #{sbAdmin.currencyutils.formatValue(paymentDetails.inQty, paymentDetails.inAsset)} from #{paymentDetails.source}."]

    return sbAdmin.form.mValueDisplay("Payment Status", {id: "payment-status", class: "payment-status-#{status}" }, msg)


module.exports = botPaymentUtils
