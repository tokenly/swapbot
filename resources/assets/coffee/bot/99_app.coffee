window.BotApp = 
    init: (bot)->
        React.render <SwapStatuses bot={bot} />, document.getElementById('SwapStatuses')
        React.render <SwapsList bot={bot} />, document.getElementById('SwapsList')

