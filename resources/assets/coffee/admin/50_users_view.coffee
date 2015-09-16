# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
# ---- end references

ctrl = {}

ctrl.usersView = {}

# ### helpers #####################################


# ################################################

vm = ctrl.usersView.vm = do ()->
    vm = {}
    vm.init = ()->
        # swapusers
        vm.users = m.prop([])
        sbAdmin.api.getAllUsers().then (usersList)->
            vm.users(usersList)
            return

        return
    return vm


ctrl.usersView.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # init
    vm.init()

    return

ctrl.usersView.view = ()->
    mEl = m("div", [
        m("h2", "API Users"),

        m("div", {class: "spacer1"}),


        m("div", { class: "row"}, [
            m("div", {class: "col-md-6 col-lg-4"}, [
        
                m("ul", {class: "list-unstyled striped-list user-list"}, [
                    vm.users().map((user)->
                        return m("li", {}, [
                            m("div", {}, [
                                m("a[href='/admin/edit/user/#{user.id}']", {class: "", config: m.route}, "#{user.name}"),
                                " ",
                                m("a[href='/admin/edit/user/#{user.id}']", {class: "usersView-edit-link pull-right", config: m.route}, [
                                    m("span", {class: "glyphicon glyphicon-edit", title: "Edit User #{user.name}"}, ''),
                                    " Edit",
                                ]),
                            ])
                        ])
                    )
                ]),
            ]),
        ]),
            

        m("div", {class: "spacer1"}),

        m("a[href='/admin/edit/user/new']", {class: "btn btn-primary", config: m.route}, "Create a new user"),
        
    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.usersView
