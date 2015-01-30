do ()->

    sbAdmin.ctrl.login = {}


    vm = sbAdmin.ctrl.login.vm = do ()->
        vm = {}
        vm.init = ()->
            vm.apiToken = m.prop('')
            vm.apiSecretKey = m.prop('')

            vm.errorMessage = m.prop('')

            vm.login = (e)->
                e.preventDefault()

                # clear the error
                vm.errorMessage('')

                # try login
                sbAdmin.auth.login(vm.apiToken(), vm.apiSecretKey()).then ()->
                    # success - redirect to the dashboard
                    m.route('/dashboard')
                , (error)->
                    # an error occurred
                    vm.errorMessage(error.message)
                    return




                return
            return
        return vm

    sbAdmin.ctrl.login.controller = ()->
        vm.init()
        return

    sbAdmin.ctrl.login.view = ()->
        return m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("h2", "Please Login to Continue"),
                    m("p", "Enter your API credentials below to save them in your browser."),

                    m("div", {class: "spacer1"}),

                    m("form", {onsubmit: vm.login, }, [
                        do ()->
                            return null if vm.errorMessage() == ''
                            return m("div", {class: "alert alert-danger", role: "alert", }, [
                                m("strong", "An error occurred. "),
                                m('span', vm.errorMessage()),
                            ])
                        ,

                        m("div", {class: "form-group"}, [
                            m("label", {for: 'apiToken'}, "API Token"),
                            m("input", {
                                id: 'apiToken', class: 'form-control', placeholder: "Your API Token", required: true, onchange: m.withAttr("value", vm.apiToken), value: vm.apiToken()
                            }),
                        ]),
                        m("div", {class: "form-group"}, [
                            m("label", {for: 'apiSecretKey'}, "API Secret Key"),
                            m("input", {
                                type: 'password', id: 'apiSecretKey', class: 'form-control', placeholder: "Your API Secret Key", required: true, onchange: m.withAttr("value", vm.apiSecretKey), value: vm.apiSecretKey()
                            }),
                        ]),

                        m("div", {class: "spacer1"}),
                        m("button", {type: 'submit', class: 'btn btn-primary'}, "Save Credentials"),

                    ]),

                ]),
            ]),



        ])

