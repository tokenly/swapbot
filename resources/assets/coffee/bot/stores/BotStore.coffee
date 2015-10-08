# ---- begin references
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}
eventEmitter = null

storedBots = {}


emitChange = ()->
    eventEmitter.emitEvent('change')
    return


updateBot = (newBot)->
    storedBots[newBot.id] = normalizeBot(newBot)
    emitChange()
    return

normalizeBot = (bot)->
    # apply swap rules
    swapRulesById = {}
    bot.swapRules?.map (swapRule)->
        swapRulesById[swapRule.uuid] = swapRule
        return

    bot.swaps = bot.swaps.map (swap)->
        if swap.swap_rule_ids? then swap.swap_rule_ids.map (swapRuleId)->
            swapRule = swapRulesById[swapRuleId]
            if swapRule
                if not swap.swapRules? then swap.swapRules = []
                swap.swapRules.push(swapRule)
            return
        return swap

    return bot

# #############################################

exports.init = (bot)->
    # init emitter
    eventEmitter = new window.EventEmitter()

    storedBots[bot.id] = normalizeBot(bot)

    return

exports.getBot = (botId)->
    return storedBots[botId]

exports.updateBot = (newBot)->
    updateBot(newBot)
    return


exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports
