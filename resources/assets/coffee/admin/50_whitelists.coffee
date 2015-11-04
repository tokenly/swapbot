# ---- begin references
SwapbotAPI = require './10_api_functions'
SwapbotAuth = require './10_auth_functions'
SwapbotNav = require './10_nav'
# ---- end references

ctrl = {}

ctrl.whitelists = {}


# ################################################

vm = ctrl.whitelists.vm = do ()->
    vm = {}
    vm.init = ()->
        vm.user = m.prop(SwapbotAuth.getUser())

        # swapbots
        vm.whitelists = m.prop([])
        SwapbotAPI.getAllWhitelists().then (whitelists)->
            vm.whitelists(whitelists)
            return

        return
    return vm


ctrl.whitelists.controller = ()->
    # require login
    SwapbotAuth.redirectIfNotLoggedIn()

    # init
    vm.init()

    return

ctrl.whitelists.view = ()->
    if vm.whitelists().length
        whitelistsEl = [
            m("p", {class: ""}, "Here is a list of your Whitelists:"),

            m("div", { class: "row"}, [
                m("div", {class: "col-md-10 col-lg-8"}, [
                    m("ul", {class: "list-unstyled striped-list whitelist-list"}, [
                        vm.whitelists().map((whitelist)->
                            return m("li", {}, [
                                m("div", {}, [
                                    m("a[href='/admin/edit/whitelist/#{whitelist.id}']", {class: "", config: m.route}, "#{whitelist.name}"),
                                    " ",
                                    m("a[href='/admin/edit/whitelist/#{whitelist.id}']", {class: "dashboard-edit-link pull-right", config: m.route}, [
                                        m("span", {class: "glyphicon glyphicon-edit", title: "Edit Whitelist #{whitelist.name}"}, ''),
                                        " Edit",
                                    ]),
                                ])
                            ])
                        )
                    ]),
                ]),
            ]),
        ]
    else
        whitelistsEl = 
        [
            m("p", {class: ""}, [
                "You don't have any whitelists yet.  After you create some whitelists, they will be listed here.",
            ]),
            m("div", {class: "spacer1"}),

        ]

    mEl = m("div", [
        m("h2", "Manage Whitelists"),
        m("p", {class: ""}, "Upload large whitelists that can be applied to one or more of your swapbots."),

        m("div", {class: "spacer1"}),

        whitelistsEl,

        m("div", {class: "spacer1"}),

        m("a[href='/admin/edit/whitelist/new']", {class: "btn btn-primary", config: m.route}, "Create a New Whitelist"),
        
    ])
    return [SwapbotNav.buildNav(), SwapbotNav.buildInContainer(mEl)]


######
module.exports = ctrl.whitelists
