Pockets = do ()->
    exports = {}

    pocketsUrl = null
    pocketsImage = null

    exports.buildPaymentButton = (address, label, amount=null, acceptedTokens='btc')->
        return null if not pocketsUrl

        encodedLabel = encodeURIComponent(label).replace(/[!'()*]/g, escape)
        urlAttributes = "?address="+address+"&label="+encodedLabel+"&tokens="+acceptedTokens;
        if amount?
            urlAttributes += '&amount='+swapbot.formatters.formatCurrencyAsNumber(amount)
        return React.createElement('a', {href: pocketsUrl+urlAttributes, target: '_blank', className: 'pocketsLink', title: "Pay Using Tokenly Pockets"}, [
            React.createElement('img', {src: pocketsImage, height: '24px', 'width': '24px'}),
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
    return exports
