# ---- begin references
BotAPIActionCreator           = require './actionCreators/BotAPIActionCreator'
QuotebotActionCreator         = require './actionCreators/QuotebotActionCreator'
SwapAPIActionCreator          = require './actionCreators/SwapAPIActionCreator'
UIActionListeners             = require './actionCreators/UIActionListeners'
GlobalAlertActionCreator      = require './actionCreators/GlobalAlertActionCreator'
Settings                      = require './constants/Settings'
BotStore                      = require './stores/BotStore'
BotstreamStore                = require './stores/BotstreamStore'
QuotebotStore                 = require './stores/QuotebotStore'
SwapsStore                    = require './stores/SwapsStore'
GlobalAlertStore              = require './stores/GlobalAlertStore'
UserChoiceStore               = require './stores/UserChoiceStore'
UserInterfaceStateStore       = require './stores/UserInterfaceStateStore'
GlobalAlertComponent          = require './views/GlobalAlertComponent'
BotCopyableAddress            = require './views/BotCopyableAddress'
BotStatusComponent            = require './views/BotStatusComponent'
RecentAndActiveSwapsComponent = require './views/RecentAndActiveSwapsComponent'
SwapPurchaseStepsComponent    = require './views/steps/SwapPurchaseStepsComponent'
UserInterfaceActions          = require './actions/UserInterfaceActions'
# ---- end references

window.BotApp = 
    init: (bot, quotebotCredentials, pusherURL)->
        # init the stores
        SwapsStore.init()
        BotstreamStore.init()
        QuotebotStore.init()
        UserChoiceStore.init()
        GlobalAlertStore.init()
        UserInterfaceStateStore.init()
        BotStore.init(bot)

        # subscribe to the swaps event stream
        BotAPIActionCreator.subscribeToBotstream(bot.id)
        SwapAPIActionCreator.init(bot.id)

        QuotebotActionCreator.subscribeToQuotebot(quotebotCredentials.url, quotebotCredentials.apiToken, pusherURL)
        GlobalAlertActionCreator.init()

        # bind misc UI events
        UIActionListeners.init()

        # render the components

        # Bot copyable address
        React.render <GlobalAlertComponent />,                         document.getElementById('GlobalAlertComponent')

        # Bot copyable address
        React.render <BotCopyableAddress            bot={bot} />,      document.getElementById('BotCopyableAddress')

        # Bot status (active/inactive)
        React.render <BotStatusComponent            bot={bot} />,      document.getElementById('BotStatusComponent')

        # recent and active swaps
        React.render <RecentAndActiveSwapsComponent botid={bot.id} />, document.getElementById('RecentAndActiveSwapsComponent')

        # run the swap interface
        React.render <SwapPurchaseStepsComponent    botid={bot.id} />, document.getElementById('SwapPurchaseStepsComponent')

    beginSwaps: ()->
        UserInterfaceActions.beginSwaps()
        return