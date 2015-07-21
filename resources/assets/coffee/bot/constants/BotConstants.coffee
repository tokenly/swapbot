BotConstants = do ()->
    exports = {}

    exports.BOT_ADD_NEW_SWAPS                = 'BOT_ADD_NEW_SWAPS'
    exports.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS = 'BOT_HANDLE_NEW_SWAPSTREAM_EVENTS'
    exports.BOT_HANDLE_NEW_BOTSTREAM_EVENTS  = 'BOT_HANDLE_NEW_BOTSTREAM_EVENTS'

    exports.BOT_USER_CHOOSE_OUT_ASSET        = 'BOT_USER_CHOOSE_OUT_ASSET'
    exports.BOT_USER_CHOOSE_SWAP_CONFIG      = 'BOT_USER_CHOOSE_SWAP_CONFIG'
    exports.BOT_USER_CHOOSE_SWAP             = 'BOT_USER_CHOOSE_SWAP'
    exports.BOT_USER_CLEAR_SWAP              = 'BOT_USER_CLEAR_SWAP'
    exports.BOT_USER_RESET_SWAP              = 'BOT_USER_RESET_SWAP'
    exports.BOT_USER_CHOOSE_OUT_AMOUNT       = 'BOT_USER_CHOOSE_OUT_AMOUNT'
    exports.BOT_UPDATE_EMAIL_VALUE           = 'BOT_UPDATE_EMAIL_VALUE'
    exports.BOT_UPDATE_EMAIL_LEVEL_VALUE     = 'BOT_UPDATE_EMAIL_LEVEL_VALUE'
    exports.BOT_USER_SUBMIT_EMAIL            = 'BOT_USER_SUBMIT_EMAIL'

    exports.BOT_GO_BACK                      = 'BOT_GO_BACK'
    exports.BOT_SHOW_ALL_TRANSACTIONS        = 'BOT_SHOW_ALL_TRANSACTIONS'
    exports.BOT_IGNORE_ALL_PREVIOUS_SWAPS    = 'BOT_IGNORE_ALL_PREVIOUS_SWAPS'

    exports.BOT_ADD_NEW_QUOTE                = 'BOT_ADD_NEW_QUOTE'

    exports.UI_BEGIN_SWAPS                   = 'UI_BEGIN_SWAPS'

    exports.UI_UPDATE_MAX_SWAPS_TO_SHOW      = 'UI_UPDATE_MAX_SWAPS_TO_SHOW'
    exports.UI_UPDATE_MAX_SWAPS_REQUESTED    = 'UI_UPDATE_MAX_SWAPS_REQUESTED'
    exports.UI_SWAPS_LOADING_BEGIN           = 'UI_SWAPS_LOADING_BEGIN'
    exports.UI_SWAPS_LOADING_END             = 'UI_SWAPS_LOADING_END'


    # #############################################
    return exports
