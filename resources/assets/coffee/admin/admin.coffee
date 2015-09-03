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
