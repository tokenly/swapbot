# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.quotebotSubscriber = require './10_quotebot_subscriber'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.allbots = require './50_all_bots'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.allswaps = require './50_all_swaps'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.botForm = require './50_bot_form'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.botPaymentsView = require './50_bot_payments_view'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.botShutdownForm = require './50_bot_shutdown_form'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.botView = require './50_bot_view'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.dashboard = require './50_dashboard'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.login = require './50_login'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.logout = require './50_logout'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.settingsForm = require './50_settings_form'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.settingsView = require './50_settings_view'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.swapEvents = require './50_swap_events'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.userForm = require './50_user_form'
sbAdmin = sbAdmin or {}; sbAdmin.ctrl = sbAdmin.ctrl or {}; sbAdmin.ctrl.usersView = require './50_users_view'
# ---- end references

# ########################################################################################################################

# routes
m.route.mode = "pathname"
m.route(
    document.getElementById('admin'),
    "/admin/dashboard",
    {
        "/admin/login"             : sbAdmin.ctrl.login,
        "/admin/logout"            : sbAdmin.ctrl.logout,
        "/admin/dashboard"         : sbAdmin.ctrl.dashboard,
        "/admin/edit/bot/:id"      : sbAdmin.ctrl.botForm,
        "/admin/view/bot/:id"      : sbAdmin.ctrl.botView,
        "/admin/shutdown/bot/:id"  : sbAdmin.ctrl.botShutdownForm,
        "/admin/payments/bot/:id"  : sbAdmin.ctrl.botPaymentsView,
        "/admin/users"             : sbAdmin.ctrl.usersView,
        "/admin/edit/user/:id"     : sbAdmin.ctrl.userForm,
        "/admin/settings"          : sbAdmin.ctrl.settingsView,
        "/admin/edit/setting/:id"  : sbAdmin.ctrl.settingsForm,
        "/admin/allbots"           : sbAdmin.ctrl.allbots,
        "/admin/allswaps"          : sbAdmin.ctrl.allswaps,
        "/admin/swapevents/:id"    : sbAdmin.ctrl.swapEvents,
    }
)

# init the quotebot
sbAdmin.quotebotSubscriber.initSubscriber(window.QUOTEBOT_URL, window.QUOTEBOT_API_TOKEN, window.QUOTEBOT_PUSHER_URL);
