# nav functions
sbAdmin.nav = do ()->
    nav = {}

    buildRightNav = (user)->
        username = user?.name
        if username
            return m("ul", { class: "nav navbar-nav navbar-right"}, [
                    m("li", { class: "dropdown"}, [
                        m("a[href=#]", {class: "dropdown-toggle", "data-toggle": "dropdown", "role": "button", "aria-expanded": "false",}, [
                            username,
                            m("span", {class: "caret"})
                        ]),
                        m("ul", { class: "dropdown-menu", role: "menu"}, [
                            m("li", { class: ""}, [
                                m("a[href='/admin/logout']", {class: "", config: m.route}, "Logout"),
                            ]),
                        ]),
                    ]),
                ])
        else
            return m("ul", { class: "nav navbar-nav navbar-right"}, [
                    m("li", { class: ""}, [
                        m("a[href='/admin/login']", {class: "", config: m.route}, "Login"),
                    ]),
                ])

    buildUsersNavLink = (user)->
        if user.privileges?.createUser
            return m("li", { class: ""}, [
                m("a[href='/admin/users']", {class: "", config: m.route}, "Users"),
            ])
        return null

    buildSettingsNavLink = (user)->
        if user.privileges?.manageSettings
            return m("li", { class: ""}, [
                m("a[href='/admin/settings']", {class: "", config: m.route}, "Settings"),
            ])
        return null

    # clone an object
    nav.buildNav = ()->
        user = sbAdmin.auth.getUser()

        return m("nav", { class: "navbar navbar-default"}, [
            # m("div", { class: "navbar navbar-default"}, []),
            m("div", { class: "container-fluid"}, [
                m("div", { class: "navbar-header"}, [
                    m("a[href='/admin/dashboard']", {class: "navbar-brand", config: m.route}, "Swapbot Admin"),
                ]),
                m("ul", { class: "nav navbar-nav"}, [
                    m("li", { class: ""}, [
                        m("a[href='/admin/dashboard']", {class: "", config: m.route}, "Dashboard"),
                    ]),
                    m("li", { class: ""}, [
                        m("a[href='/admin/edit/bot/new']", {class: "", config: m.route}, "New Bot"),
                    ]),
                    buildUsersNavLink(user),
                    buildSettingsNavLink(user),
                ]),
                buildRightNav(user),
            ]),
        ])

    nav.buildInContainer = (mEl)->
        return m("div", { class: "container", style: {marginTop: "0px", marginBottom: "24px"}}, [
            m("div", { class: "row"}, [
                m("div", { class: "col-md-12 col-lg-10 col-lg-offset-1"}, [
                    mEl
                ])
            ])
        ])






    return nav
