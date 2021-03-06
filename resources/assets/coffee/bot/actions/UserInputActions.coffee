# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
# ---- end references

exports = {}

exports.chooseAsset = (asset, isSell)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_ASSET
        asset     : asset
        isSell    : isSell
    })
    return

exports.chooseSwapConfigAtRate = (chosenSwapConfig, currentRate)->
    Dispatcher.dispatch({
        actionType:  BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG
        swapConfig:  chosenSwapConfig
        currentRate: currentRate
    })
    return

exports.updateSwapAmount = (newAmount, amountType)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_ENTERED_SWAP_AMOUNT
        amount: newAmount
        amountType: amountType
    })
    return

exports.chooseSwap = (swap)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_SWAP
        swap      : swap
    })
    return

exports.clearSwap = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CLEAR_SWAP
    })
    return

exports.resetSwap = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_RESET_SWAP
    })
    return



exports.confirmWallet = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CONFIRM_WALLET
    })
    return


exports.updateEmailValue = (email)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_UPDATE_EMAIL_VALUE
        email: email
    })
    return

exports.updateEmailLevel = (level)->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_UPDATE_EMAIL_LEVEL_VALUE
        level: level
    })
    return

exports.submitEmail = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_SUBMIT_EMAIL
    })
    return



exports.goBackOnClick = (e)->
    e.preventDefault()
    exports.goBack()
    return

exports.goBack = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_GO_BACK
    })
    return


exports.showAllTransactionsOnClick = (e)->
    e.preventDefault()
    exports.showAllTransactions()
    return

exports.showAllTransactions = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_SHOW_ALL_TRANSACTIONS
    })
    return


exports.ignoreAllSwapsOnClick = (e)->
    e.preventDefault()
    exports.ignoreAllSwaps()
    return

exports.ignoreAllSwaps = ()->
    Dispatcher.dispatch({
        actionType: BotConstants.BOT_IGNORE_ALL_PREVIOUS_SWAPS
    })
    return



# #############################################
module.exports = exports
