do ()->

    sbAdmin.ctrl.userForm = {}

    # ### helpers #####################################

    formatPrivileges = (privileges)->
        # privilegeTypes = {
        #     {name: "createUser", label: "Create User"}
        # }
        out = for privilege, set of privileges
            privilege 
        if out.length
            return out.join(", ")
        return "No Privileges"
        

    # ################################################

    vm = sbAdmin.ctrl.userForm.vm = do ()->
        vm = {}
        vm.init = ()->
            # view status
            vm.errorMessages = m.prop([])
            vm.formStatus = m.prop('active')
            vm.resourceId = m.prop('')

            # fields
            vm.name         = m.prop('')
            vm.email        = m.prop('')
            vm.apitoken     = m.prop('')
            vm.apisecretkey = m.prop('')
            vm.privileges   = m.prop('')

            # if there is an id, then load it from the api
            id = m.route.param('id')
            if id != 'new'
                # load the user info from the api
                sbAdmin.api.getUser(id).then(
                    (userData)->
                        vm.resourceId(userData.id)

                        vm.name(userData.name)
                        vm.email(userData.email)
                        vm.apitoken(userData.apitoken)
                        vm.apisecretkey(userData.apisecretkey)
                        vm.privileges(userData.privileges)

                        return
                    , (errorResponse)->
                        vm.errorMessages(errorResponse.errors)
                        return
                )


            vm.save = (e)->
                e.preventDefault()

                attributes = {
                    name: vm.name()
                    email: vm.email()
                }

                if vm.resourceId().length > 0
                    # update existing user
                    apiCall = sbAdmin.api.updateUser
                    apiArgs = [vm.resourceId(), attributes]
                else
                    # new user
                    apiCall = sbAdmin.api.newUser
                    apiArgs = [attributes]

                sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(()->
                    # back to users
                    m.route('/users')
                    return
                )

            return
        return vm

    sbAdmin.ctrl.userForm.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        vm.init()
        return

    sbAdmin.ctrl.userForm.view = ()->
        mEl = m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("h2", if vm.resourceId() then "Edit User #{vm.name()}" else "Create a New User"),

                    m("div", {class: "spacer1"}),

                    # m("form", {onsubmit: vm.save, }, [
                    sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                        sbAdmin.form.mAlerts(vm.errorMessages),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-5"}, [
                                sbAdmin.form.mFormField("Name", {id: 'name', 'placeholder': "User Name", required: true, }, vm.name),
                            ]),
                            m("div", {class: "col-md-7"}, [
                                sbAdmin.form.mFormField("Email", {type: 'email', id: 'email', 'placeholder': "User Email", required: true, }, vm.email),
                            ]),
                        ]),

                        m("hr"),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.form.mValueDisplay("API Token", {id: "apitoken", }, vm.apitoken()),
                            ]),
                            m("div", {class: "col-md-8"}, [
                                sbAdmin.form.mValueDisplay("API Secret Key", {id: "apisecretkey", }, vm.apisecretkey()),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-6"}, [
                                sbAdmin.form.mValueDisplay("privileges", {id: "apitoken", }, formatPrivileges(vm.privileges())),
                            ]),
                        ]),




                        m("div", {class: "spacer1"}),

                        sbAdmin.form.mSubmitBtn("Save User"),
                        m("a[href='/users']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                        

                    ]),

                ]),
            ]),



        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


