# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
# ---- end references

ctrl = {}

ctrl.logout = {}



ctrl.logout.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # no vm
    sbAdmin.auth.logout()
    return

ctrl.logout.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("h2", "Logged Out"),
                m("p", "The API credentials have been cleared from your browser."),

                m("div", {class: "spacer1"}),

                m("a[href='/admin/login']", {config: m.route}, "Return to Login"),
            ]),
        ]),



    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]

######
module.exports = ctrl.logout
