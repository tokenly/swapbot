# quotebotSubscriber functions
sbAdmin.quotebotSubscriber = do ()->
    currentQuote = null
    changeListeners = {}
    changeListenerID = 0

    quotebotSubscriber = {}

    handleQuotebotUpdate = (quote)->
        currentQuote = quote

        for id, changeListenerCallback of changeListeners
            changeListenerCallback(currentQuote.last, currentQuote)
        
        return


    # subscribe
    quotebotSubscriber.initSubscriber = (quotebotURL, apiToken, quotebotPusherURL)->
        path = "#{quotebotURL}/api/v1/quote/all?apitoken=#{apiToken}"

        opts = {
            method: 'GET',
            url: path,
            background: true,
        }

        m.request(opts).then(
            (quotesJSON)->
                if quotesJSON.quotes?
                    for quote in quotesJSON.quotes
                        if quote.source == 'bitcoinAverage' and quote.pair == 'USD:BTC'
                            handleQuotebotUpdate(quote)
                    return
            , (errorResponse)->
                console.error(errorResponse.errors)
                return
        )

        # also subscribe to the pusher events from quotebot
        pusherClient = sbAdmin.pusherutils.subscribeToPusherChanel("quotebot_quote_bitcoinAverage_USD_BTC", handleQuotebotUpdate, quotebotPusherURL)

        return




    quotebotSubscriber.addChangeListener = (changeListenerCallback)->
        changeListeners[++changeListenerID] = changeListenerCallback
        if currentQuote?
            changeListenerCallback(currentQuote.last, currentQuote)
        return changeListenerID

    quotebotSubscriber.removeChangeListener = (id)->
        delete changeListeners[id]
        return



    return quotebotSubscriber

