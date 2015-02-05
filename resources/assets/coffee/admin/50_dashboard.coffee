do ()->

    sbAdmin.ctrl.dashboard = {}

    # ### helpers #####################################

    listSwapbots = ()->
        sbAdmin.api.getBots().then (botsList)->
            return m.prop(botsList)

    # ################################################

    vm = sbAdmin.ctrl.dashboard.vm = do ()->
        vm = {}
        vm.init = ()->
            vm.user = m.prop(sbAdmin.auth.getUser())

            # swapbots
            vm.bots = m.prop([])
            sbAdmin.api.getAllBots().then (botsList)->
                vm.bots(botsList)
                # m.redraw(true)
                return

            return
        return vm


    sbAdmin.ctrl.dashboard.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        # init
        vm.init()

        return

    sbAdmin.ctrl.dashboard.view = ()->
        mEl = m("div", [
            m("h2", "Welcome, #{vm.user().name}"),

            m("div", {class: "spacer1"}),


            m("div", { class: "row"}, [
                m("div", {class: "col-md-6 col-lg-4"}, [
            
                    m("ul", {class: "list-unstyled striped-list bot-list"}, [
                        vm.bots().map((bot)->
                            return m("li", {}, [
                                m("div", {}, [
                                    m("a[href='/view/bot/#{bot.id}']", {class: "", config: m.route}, "#{bot.name}"),
                                    " ",
                                    m("a[href='/edit/bot/#{bot.id}']", {class: "dashboard-edit-link pull-right", config: m.route}, [
                                        m("span", {class: "glyphicon glyphicon-edit", title: "Edit Swapbot #{bot.name}"}, ''),
                                        " Edit",
                                    ]),
                                ])
                            ])
                        )
                    ]),
                ]),
            ]),
                

            m("div", {class: "spacer1"}),

            m("a[href='/edit/bot/new']", {class: "btn btn-primary", config: m.route}, "Create a new Swapbot"),
            
        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


    ######
