window.BotApp = 
    init: (bot, quotebotCredentials, pusherURL)->
        # init the stores
        SwapsStore.init()
        BotstreamStore.init()
        QuotebotStore.init()
        UserChoiceStore.init()
        UserInterfaceStateStore.init()
        BotStore.init(bot)

        # subscribe to the swaps event stream
        BotAPIActionCreator.subscribeToBotstream(bot.id)
        SwapAPIActionCreator.init(bot.id)

        QuotebotActionCreator.subscribeToQuotebot(quotebotCredentials.url, quotebotCredentials.apiToken, pusherURL)

        # bind misc UI events
        UIActionListeners.init()

        # render the components

        # Bot copyable address
        React.render <BotCopyableAddress            bot={bot} />,      document.getElementById('BotCopyableAddress')

        # Bot status (active/inactive)
        React.render <BotStatusComponent            bot={bot} />,      document.getElementById('BotStatusComponent')

        # recent and active swaps
        React.render <RecentAndActiveSwapsComponent botid={bot.id} />, document.getElementById('RecentAndActiveSwapsComponent')

        # run the swap interface
        React.render <SwapPurchaseStepsComponent    botid={bot.id} />, document.getElementById('SwapPurchaseStepsComponent')
