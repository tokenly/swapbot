# ---- begin references
SwapbotAPI      = require './10_api_functions'
SwapbotAuth     = require './10_auth_functions'
AdminForm       = require './10_form_helpers'
SwapbotAdminNav = require './10_nav'
# ---- end references

ctrl = {}

ctrl.botShutdownForm = {}




# ################################################

vm = ctrl.botShutdownForm.vm = do ()->
    vm = {}
    vm.init = ()->
        # view status
        vm.errorMessages = m.prop([])
        vm.formStatus = m.prop('active')
        vm.resourceId = m.prop('')

        # fields
        vm.name = m.prop('')
        vm.data = m.prop('')
        
        # if there is an id, then load it from the api
        id = m.route.param('id')
        # load the bot info from the api
        SwapbotAPI.getWhitelist(id).then(
            (whitelistData)->
                vm.resourceId(whitelistData.id)

                vm.name(whitelistData.name)
                vm.data(whitelistData.data)

                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )

       

        vm.doShutdown = (e)->
            e.preventDefault()

            # update existing bot
            apiArgs = [vm.resourceId()]

            AdminForm.submit(SwapbotAPI.deleteWhitelist, apiArgs, vm.errorMessages, vm.formStatus).then((apiResponse)->
                # console.log "submit complete - routing to dashboard"
                # go to bot display
                botId = vm.resourceId()
                m.route("/admin/whitelists")
                return
            )

        return
    return vm

ctrl.botShutdownForm.controller = ()->
    # require login
    SwapbotAuth.redirectIfNotLoggedIn()

    vm.init()
    return

ctrl.botShutdownForm.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [

                m("div", { class: "row"}, [
                    m("div", {class: "col-md-10"}, [
                        m("h2", if vm.resourceId() then "Delete Whitelist #{vm.name()}" else ""),
                    ]),
                ]),


                m("div", {class: "spacer1"}),

                # m("form", {onsubmit: vm.doShutdown, }, [
                AdminForm.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.doShutdown}, [
                    AdminForm.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            m("div", {class: "spacer2"}),
                            m("div", {class: "panel panel-danger"}, [
                                m("div", {class: 'panel-heading'}, [
                                    m("h4", {class: 'panel-title'}, 
                                        "Are you sure you want to delete this whitelist?"
                                    ),
                                ]),
                                m("div", {class: 'panel-body'},
                                    "Any bots using this whitelist will be updated."
                                ),
                            ])
                        ]),
                    ]),

                    m("div", {class: "spacer1"}),

                    m("a[href='/admin/whitelists']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                    AdminForm.mSubmitBtn("Permanently Delete this Whitelist", 'btn btn-danger'),
                    
                ]),

            ]),
        ]),



    ])
    return [SwapbotAdminNav.buildNav(), SwapbotAdminNav.buildInContainer(mEl)]


######
module.exports = ctrl.botShutdownForm
