sbAdmin = sbAdmin or {};
sbAdmin.ctrl = sbAdmin.ctrl or {};

# ---- begin references
QuotebotSubscriber               = require './10_quotebot_subscriber'
GlobalAlertSubscriber            = require './10_global_alert_subscriber'
GlobalAlertPanel                 = require './10_global_alert_panel'
sbAdmin.ctrl.allbots             = require './50_all_bots'
sbAdmin.ctrl.allswaps            = require './50_all_swaps'
sbAdmin.ctrl.botForm             = require './50_bot_form'
sbAdmin.ctrl.botPaymentsView     = require './50_bot_payments_view'
sbAdmin.ctrl.botShutdownForm     = require './50_bot_shutdown_form'
sbAdmin.ctrl.botView             = require './50_bot_view'
sbAdmin.ctrl.dashboard           = require './50_dashboard'
sbAdmin.ctrl.login               = require './50_login'
sbAdmin.ctrl.logout              = require './50_logout'
sbAdmin.ctrl.settingsForm        = require './50_settings_form'
sbAdmin.ctrl.globalAlertForm     = require './50_global_alert_form'
sbAdmin.ctrl.globalAlertSaved    = require './50_global_alert_saved_view'
sbAdmin.ctrl.settingsView        = require './50_settings_view'
sbAdmin.ctrl.swapEvents          = require './50_swap_events'
sbAdmin.ctrl.userForm            = require './50_user_form'
sbAdmin.ctrl.usersView           = require './50_users_view'
sbAdmin.ctrl.whitelists          = require './50_whitelists'
sbAdmin.ctrl.whitelistForm       = require './50_whitelist_form'
sbAdmin.ctrl.whitelistDeleteForm = require './50_whitelist_delete_form'
# ---- end references

# ########################################################################################################################

# routes
m.route.mode = "pathname"
m.route(
    document.getElementById('admin'),
    "/admin/dashboard",
    {
        "/admin/login"              : sbAdmin.ctrl.login,
        "/admin/logout"             : sbAdmin.ctrl.logout,
        "/admin/dashboard"          : sbAdmin.ctrl.dashboard,
        "/admin/edit/bot/:id"       : sbAdmin.ctrl.botForm,
        "/admin/view/bot/:id"       : sbAdmin.ctrl.botView,
        "/admin/shutdown/bot/:id"   : sbAdmin.ctrl.botShutdownForm,
        "/admin/payments/bot/:id"   : sbAdmin.ctrl.botPaymentsView,
        "/admin/users"              : sbAdmin.ctrl.usersView,
        "/admin/edit/user/:id"      : sbAdmin.ctrl.userForm,
        "/admin/settings"           : sbAdmin.ctrl.settingsView,
        "/admin/edit/setting/:id"   : sbAdmin.ctrl.settingsForm,
        "/admin/globalalert"        : sbAdmin.ctrl.globalAlertForm,
        "/admin/globalalert/saved"  : sbAdmin.ctrl.globalAlertSaved,
        "/admin/allbots"            : sbAdmin.ctrl.allbots,
        "/admin/allswaps"           : sbAdmin.ctrl.allswaps,
        "/admin/swapevents/:id"     : sbAdmin.ctrl.swapEvents,

        "/admin/whitelists"         : sbAdmin.ctrl.whitelists,
        "/admin/edit/whitelist/:id" : sbAdmin.ctrl.whitelistForm,
        "/admin/delete/whitelist/:id" : sbAdmin.ctrl.whitelistDeleteForm,
        # "/admin/view/whitelist/:id" : sbAdmin.ctrl.whitelistView,
    }
)

# init the quotebot
QuotebotSubscriber.initSubscriber(window.QUOTEBOT_URL, window.QUOTEBOT_API_TOKEN, window.QUOTEBOT_PUSHER_URL)

# init global alert listener
GlobalAlertSubscriber.initSubscriber()
GlobalAlertPanel.init()