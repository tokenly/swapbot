QuotebotActionCreator = do ()->
    exports = {}

    subscriberId = null

    exports.subscribeToQuotebot = (quotebotURL, apiToken, pusherURL)->
        # load all existing botstream events
        $.get "#{quotebotURL}/api/v1/quote/all?apitoken=#{apiToken}", (quotesJSON)=>
            if quotesJSON.quotes?
                for quote in quotesJSON.quotes
                    if quote.source == 'bitcoinAverage' and quote.pair == 'USD:BTC'
                        QuotebotEventActions.addNewQuote(quote)
            return

        # subscribe to the pusher events
        subscriberId = swapbot.pusher.subscribeToPusherChanel "quotebot_quote_bitcoinAverage_USD_BTC", (quote)->
            QuotebotEventActions.addNewQuote(quote)
            return
        , null, pusherURL

        return



    # #############################################
    return exports


