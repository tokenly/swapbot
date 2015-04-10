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
            
            m("p", {class: ""}, "Here is a list of your Swapbots:"),

            m("div", { class: "row"}, [
                m("div", {class: "col-md-6 col-lg-4"}, [
                    m("ul", {class: "list-unstyled striped-list bot-list"}, [
                        vm.bots().map((bot)->
                            return m("li", {}, [
                                m("div", {}, [
                                    if bot.hash.length then m("a[href='/admin/view/bot/#{bot.id}']", {config: m.route}, [m("img", {class: 'tinyRoboHead', src: "http://robohash.org/#{bot.hash}.png?set=set3"})]) else m('div', {class: 'emptyRoboHead'}, ''),
                                    m("a[href='/admin/view/bot/#{bot.id}']", {class: "", config: m.route}, "#{bot.name}"),
                                    " ",
                                    m("a[href='/admin/edit/bot/#{bot.id}']", {class: "dashboard-edit-link pull-right", config: m.route}, [
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

            m("a[href='/admin/edit/bot/new']", {class: "btn btn-primary", config: m.route}, "Create a new Swapbot"),
            
        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


    ######
