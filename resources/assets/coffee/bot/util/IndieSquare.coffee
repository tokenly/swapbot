# ---- begin references
formatters = require '../../shared/formatters'
# ---- end references

exports = {}

pocketsUrl = null
pocketsImage = null

buildPaymentLinkFns = {}

getBrowserType = ()->
    if navigator.userAgent.match(/(android)/i)
        return 'android'
    if navigator.userAgent.match(/(iPad)|(iPhone)|(iPod)/i) && !window.MSStream
        return 'ios'
    return 'other'

buildPaymentLinkFns.android = (address, label, amount, token)->
    encodedLabel = encodeURIComponent(label).replace(/[!'()*]/g, escape)
    encodedAmount = formatters.formatCurrencyAsNumber(amount)

    href = """intent://#Intent;scheme=indiewallet;package=inc.lireneosoft.counterparty;S.source=screen_to?params={"screen":"send","destination":"#{address}", "amount":#{encodedAmount}, "asset":"#{token}", "label":"#{encodedLabel}"};end"""

    return href

buildPaymentLinkFns.ios = (address, label, amount, token)->
    encodedLabel = encodeURIComponent(label).replace(/[!'()*]/g, escape)
    encodedAmount = formatters.formatCurrencyAsNumber(amount)

    # indiewallet://screen_to?params={"screen":"send","destination":"1FGtbEixucaGc2YUpwsdsN33xYkeeFLaYc", "amount":1000, "asset":"BITCRYSTALS", "label":"Swapbot%20XCPCARD%20BOT"}

    href = """indiewallet://screen_to?params={"screen":"send","destination":"#{address}", "amount":#{encodedAmount}, "asset":"#{token}", "label":"#{encodedLabel}"}"""

    return href

# ------------------------------------------------------------------------
    
exports.buildPaymentButton = (address, label, amount, token)->
    browserTypeName = getBrowserType()
    isMobile = (browserTypeName == 'android' or browserTypeName == 'ios')
    if not isMobile
        return null

    href = buildPaymentLinkFns[browserTypeName](address, label, amount, token)

    return React.createElement('a', {href: href, target: '_blank', className: 'indiesquareLink', title: "Pay with IndieSquare"}, [
        React.createElement('img', {src: '/images/indiesquare/pay-with-indiesquare.png', style: {height: "32px"}}),
    ])




# #############################################
module.exports = exports
