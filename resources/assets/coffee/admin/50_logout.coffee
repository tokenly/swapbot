do ()->

    sbAdmin.ctrl.logout = {}



    sbAdmin.ctrl.logout.controller = ()->
        # no vm
        sbAdmin.auth.logout()
        return

    sbAdmin.ctrl.logout.view = ()->
        return m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("h2", "Logged Out"),
                    m("p", "The API credentials have been cleared from your browser."),

                    m("div", {class: "spacer1"}),

                    m("a[href='/login']", {config: m.route}, "Return to Login"),
                ]),
            ]),



        ])

