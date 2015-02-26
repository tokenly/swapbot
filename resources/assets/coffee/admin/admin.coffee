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
m.route.mode = "pathname"
m.route(
    document.getElementById('admin'),
    "/admin/dashboard",
    {
        "/admin/login"     : sbAdmin.ctrl.login,
        "/admin/logout"    : sbAdmin.ctrl.logout,
        "/admin/dashboard" : sbAdmin.ctrl.dashboard,
        "/admin/edit/bot/:id"   : sbAdmin.ctrl.botForm,
        "/admin/view/bot/:id"   : sbAdmin.ctrl.botView,
        "/admin/payments/bot/:id"   : sbAdmin.ctrl.botPaymentsView,
        "/admin/users" : sbAdmin.ctrl.usersView,
        "/admin/edit/user/:id"   : sbAdmin.ctrl.userForm,
    }
)
# m.module(document.getElementById('admin'), {controller: sbAdmin.ctrl.dashboard.controller, view: sbAdmin.ctrl.dashboard.view})
