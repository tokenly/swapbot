window.BotApp = 
    init: (bot)->
        # init the stores
        SwapsStore.init()
        UserChoiceStore.init()

        # load all swaps from the server
        # SwapAPIActionCreator.loadSwapsFromAPI(bot.id)

        # subscribe to the swaps event stream
        SwapAPIActionCreator.subscribeToSwapEventStream(bot.id)

        # render the components

        # React.render <SwapTestView />, document.getElementById('RecentAndActiveSwapsComponent')

        # recent and active swaps
        React.render <RecentAndActiveSwapsComponent bot={bot} />, document.getElementById('RecentAndActiveSwapsComponent')
        # run the swap interface
        React.render <SwapInterfaceComponent bot={bot} />, document.getElementById('SwapInterfaceComponent')
