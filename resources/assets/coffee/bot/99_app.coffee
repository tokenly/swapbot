window.BotApp = 
    init: (bot)->
        eventSubscriber = swapbot.botEventsService.buildEventSubscriberForBot(bot)
        chosenSwapProvider = {
            swap: null

            registerOnSwapChange: (fn)->
                chosenSwapProvider._callbacks.push(fn)
                return
            setSwap: (swap)->
                chosenSwapProvider.swap = swap
                for fn in chosenSwapProvider._callbacks
                    fn(swap)
                return

            _callbacks: []
                
        }

        React.render <BotStatusComponent eventSubscriber={eventSubscriber} bot={bot} />, document.getElementById('BotStatusComponent')
        React.render <SwapInterfaceComponent eventSubscriber={eventSubscriber} bot={bot} chosenSwapProvider={chosenSwapProvider} />, document.getElementById('SwapInterfaceComponent')
        # React.render <SwapsListComponent bot={bot} chosenSwapProvider={chosenSwapProvider} />, document.getElementById('SwapsListComponent')
        React.render <RecentAndActiveSwapsComponent eventSubscriber={eventSubscriber} bot={bot} />, document.getElementById('RecentAndActiveSwapsComponent')

