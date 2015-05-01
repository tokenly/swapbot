window.BotApp = 
    init: (bot)->
        # init the stores
        SwapsStore.init()

        # load all swaps from the server
        # SwapAPIActionCreator.loadSwapsFromAPI(bot.id)

        # subscribe to the swaps event stream
        SwapAPIActionCreator.subscribeToSwapEventStream(bot.id)

        # render the components

        # React.render <SwapTestView />, document.getElementById('RecentAndActiveSwapsComponent')
        React.render <RecentAndActiveSwapsComponent bot={bot} />, document.getElementById('RecentAndActiveSwapsComponent')
