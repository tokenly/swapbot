# ---- begin references
QuotebotEventActions = require '../actions/QuotebotEventActions'
swapbot = swapbot or {}; swapbot.pusher = require '../../shared/pusherUtils'
# ---- end references

exports = {}

subscriberId = null

exports.subscribeToQuotebot = (quotebotURL, apiToken, pusherURL)->
    # load all existing botstream events
    $.get "#{quotebotURL}/api/v1/quote/all?apitoken=#{apiToken}", (quotesJSON)=>
        if quotesJSON.quotes?
            for quote in quotesJSON.quotes
                QuotebotEventActions.addNewQuote(quote)
        return

    # subscribe to the pusher events
    subscriberId = swapbot.pusher.subscribeToPusherChanel "quotebot_quote_all", (quote)->
        QuotebotEventActions.addNewQuote(quote)
        return
    , null, pusherURL

    return



# #############################################
module.exports = exports


