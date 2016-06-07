# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
# ---- end references

ctrl = {}

ctrl.globalAlertSaved = {}



ctrl.globalAlertSaved.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()
    return

ctrl.globalAlertSaved.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("h2", "Global Alert Saved"),
                m("p", "The settings are saved."),

                m("div", {class: "spacer1"}),

                m("a[href='/admin']", {config: m.route}, "Return to Dashboard"),
            ]),
        ]),



    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]

######
module.exports = ctrl.globalAlertSaved
