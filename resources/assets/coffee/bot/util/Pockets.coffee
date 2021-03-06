# ---- begin references
swapbot = swapbot or {}; swapbot.formatters = require '../../shared/formatters'
# ---- end references

exports = {}

pocketsUrl = null
pocketsImage = null

buildPromoLink = ()->
    isChrome = (navigator.userAgent.toLowerCase().indexOf('chrome') > -1)
    if not isChrome
        return null

    isMobile = navigator.userAgent.match(/(iPad)|(iPhone)|(iPod)|(android)|(webOS)/i)
    if isMobile
        return null

    href = "http://pockets.tokenly.com"
    return React.createElement('a', {href: href, target: '_blank', className: 'pocketsLink', title: "Learn More About Tokenly Pockets"}, [
        React.createElement('img', {src: '/images/pockets/paywithpockets-blue.png', height: '32px', 'width': '87px'}),
    ])

exports.buildPaymentButton = (address, label, amount=null, acceptedTokens='btc')->
    return buildPromoLink() if not pocketsUrl

    encodedLabel = encodeURIComponent(label).replace(/[!'()*]/g, escape)
    urlAttributes = "?address="+address+"&label="+encodedLabel+"&tokens="+acceptedTokens;
    if amount?
        urlAttributes += '&amount='+swapbot.formatters.formatCurrencyAsNumber(amount)
    return React.createElement('a', {href: pocketsUrl+urlAttributes, target: '_blank', className: 'pocketsLink', title: "Pay Using Tokenly Pockets"}, [
        # React.createElement('img', {src: pocketsImage, height: '24px', 'width': '24px'}),
        React.createElement('img', {src: pocketsImage, height: '32px', 'width': '87px'}),
    ])

exports.exists = ()->
    return pocketsUrl?

# init on document ready
jQuery ($)->
    maxAttempts = 10
    attempts = 0
    tryToLoadURL = ()->
        ++attempts

        pocketsUrl = $('.pockets-url').text()
        if pocketsUrl == ''
            pocketsUrl = null
            if attempts > maxAttempts
                # console.log "Pockets not found after #{maxAttempts} attempts - giving up"
                return
            timeoutRef = setTimeout(tryToLoadURL, 250)
            return

        pocketsImage = $('.pockets-image').text()


    tryToLoadURL()

    return


# #############################################
module.exports = exports
