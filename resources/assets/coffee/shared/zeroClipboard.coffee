# zeroClipboard functions


exports = {}

# #############################################
# local




# #############################################
# exports


exports.getStatusFromBot = (bot)->
    return if bot.state == 'active' then 'active' else 'inactive'

exports.newBotStatusFromEvent = (oldState, botEvent)->
    state = oldState
    event = botEvent.event
    switch event.name
        when 'bot.stateChange'
            if event.state == 'active'
                state = 'active'
            else
                state = 'inactive'
    return state



module.exports = exports

