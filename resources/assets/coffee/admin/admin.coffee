# codekit-prepend '01_init.coffee'

# codekit-prepend '10_utils.coffee'
# codekit-prepend '10_api_functions.coffee'
# codekit-prepend '10_auth_functions.coffee'
# codekit-prepend '10_form_helpers.coffee'

# codekit-prepend '50_dashboard.coffee'
# codekit-prepend '50_login.coffee'
# codekit-prepend '50_logout.coffee'
# codekit-prepend '50_bot_form.coffee'
# codekit-prepend '50_bot_view.coffee'

# ########################################################################################################################

# routes
m.route.mode = "hash"
m.route(
    document.getElementById('admin'),
    "/dashboard",
    {
        "/login"     : sbAdmin.ctrl.login,
        "/logout"    : sbAdmin.ctrl.logout,
        "/dashboard" : sbAdmin.ctrl.dashboard,
        "/edit/bot/:id"   : sbAdmin.ctrl.botForm,
        "/view/bot/:id"   : sbAdmin.ctrl.botView,
    }
)
# m.module(document.getElementById('admin'), {controller: sbAdmin.ctrl.dashboard.controller, view: sbAdmin.ctrl.dashboard.view})
