do ()->

    sbAdmin.ctrl.settingsView = {}

    # ### helpers #####################################


    # ################################################

    vm = sbAdmin.ctrl.settingsView.vm = do ()->
        vm = {}
        vm.init = ()->
            # settings
            vm.settings = m.prop([])
            sbAdmin.api.getAllSettings().then (settingsList)->
                vm.settings(settingsList)
                return

            return
        return vm


    sbAdmin.ctrl.settingsView.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        # init
        vm.init()

        return

    sbAdmin.ctrl.settingsView.view = ()->
        mEl = m("div", [
            m("h2", "Global Swapbot Settings"),

            m("div", {class: "spacer1"}),


            m("div", { class: "row"}, [
                m("div", {class: "col-md-6 col-lg-4"}, [
            
                    m("ul", {class: "list-unstyled striped-list setting-list"}, [
                        vm.settings().map((setting)->
                            return m("li", {}, [
                                m("div", {}, [
                                    m("a[href='/admin/edit/setting/#{setting.id}']", {class: "", config: m.route}, "#{setting.name}"),
                                    " ",
                                    m("a[href='/admin/edit/setting/#{setting.id}']", {class: "settingsView-edit-link pull-right", config: m.route}, [
                                        m("span", {class: "glyphicon glyphicon-edit", title: "Edit Setting #{setting.name}"}, ''),
                                        " Edit",
                                    ]),
                                ])
                            ])
                        ),
                        if vm.settings().length == 0
                            m("li", {}, [
                                m("div", {}, [
                                    "No settings found"
                                ]),
                            ])
                    ]),
                ]),
            ]),
                

            m("div", {class: "spacer1"}),

            m("a[href='/admin/edit/setting/new']", {class: "btn btn-primary", config: m.route}, "Create a new setting"),
            
        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


    ######
