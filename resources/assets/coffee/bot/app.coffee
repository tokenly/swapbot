window.BotApp = 
    init: (bot, quotebotCredentials, pusherURL)->
        # init the stores
        SwapsStore.init()
        BotstreamStore.init()
        QuotebotStore.init()
        UserChoiceStore.init()

        # subscribe to the swaps event stream
        BotAPIActionCreator.subscribeToBotstream(bot.id)
        SwapAPIActionCreator.subscribeToSwapstream(bot.id)

        QuotebotActionCreator.subscribeToQuotebot(quotebotCredentials.url, quotebotCredentials.apiToken, pusherURL)

        # render the components

        # Bot status (active/inactive)
        React.render <BotStatusComponent            bot={bot} />, document.getElementById('BotStatusComponent')

        # recent and active swaps
        React.render <RecentAndActiveSwapsComponent bot={bot} />, document.getElementById('RecentAndActiveSwapsComponent')

        # run the swap interface
        React.render <SwapPurchaseStepsComponent    bot={bot} />, document.getElementById('SwapPurchaseStepsComponent')
