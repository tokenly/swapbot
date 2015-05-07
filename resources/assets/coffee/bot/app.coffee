window.BotApp = 
    init: (bot)->
        # init the stores
        SwapsStore.init()
        BotstreamStore.init()
        UserChoiceStore.init()

        # load all swaps from the server
        # SwapAPIActionCreator.loadSwapsFromAPI(bot.id)

        # subscribe to the swaps event stream
        BotAPIActionCreator.subscribeToBotstream(bot.id)
        SwapAPIActionCreator.subscribeToSwapstream(bot.id)

        # render the components

        # React.render <SwapTestView />, document.getElementById('RecentAndActiveSwapsComponent')

        # Bot status (active/inactive)
        React.render <BotStatusComponent            bot={bot} />, document.getElementById('BotStatusComponent')
        # recent and active swaps
        React.render <RecentAndActiveSwapsComponent bot={bot} />, document.getElementById('RecentAndActiveSwapsComponent')
        # run the swap interface
        React.render <SwapPurchaseStepsComponent        bot={bot} />, document.getElementById('SwapPurchaseStepsComponent')
