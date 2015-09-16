# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.robohashUtils = require './10_robohash_utils'
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
        vm.allPlansData = m.prop(null)

        # fields
        vm.name = m.prop('')
        vm.description = m.prop('')
        vm.hash = m.prop('')
        vm.shutdownAddress = m.prop('')
        
        # if there is an id, then load it from the api
        id = m.route.param('id')
        # load the bot info from the api
        sbAdmin.api.getBot(id).then(
            (botData)->
                vm.resourceId(botData.id)

                vm.name(botData.name)
                vm.description(botData.description)
                vm.hash(botData.hash)

                vm.shutdownAddress(if botData.shutdownAddress? then botData.shutdownAddress else '')

                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )

       

        vm.doShutdown = (e)->
            e.preventDefault()

            attributes = {
                shutdownAddress: vm.shutdownAddress()
            }

            # update existing bot
            apiArgs = [vm.resourceId(), attributes]

            sbAdmin.form.submit(sbAdmin.api.shutdownBot, apiArgs, vm.errorMessages, vm.formStatus).then((apiResponse)->
                # console.log "submit complete - routing to dashboard"
                # go to bot display
                botId = vm.resourceId()
                m.route("/admin/view/bot/#{botId}")
                return
            )

        return
    return vm

ctrl.botShutdownForm.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    vm.init()
    return

ctrl.botShutdownForm.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [

                m("div", { class: "row"}, [
                    m("div", {class: "col-md-10"}, [
                        m("h2", if vm.resourceId() then "Shutdown SwapBot #{vm.name()}" else ""),
                    ]),
                    m("div", {class: "col-md-2 text-right"}, [
                        sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead'),
                    ]),
                ]),


                m("div", {class: "spacer1"}),

                # m("form", {onsubmit: vm.doShutdown, }, [
                sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.doShutdown}, [
                    sbAdmin.form.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            m("div", {class: "spacer2"}),
                            m("div", {class: "panel panel-danger"}, [
                                m("div", {class: 'panel-heading'}, [
                                    m("h4", {class: 'panel-title'}, 
                                        "Are you sure you want to shutdown this bot?"
                                    ),
                                ]),
                                m("div", {class: 'panel-body'},
                                    "If you shutdown this bot it will be permanently deactivated and not complete any more new swaps.  Any new swaps will be refunded automatically.  After 6 confirmations, all of the remaining funds will be forwarded to the address entered below."
                                ),
                            ])
                        ]),
                    ]),

                    m("div", {class: "spacer1"}),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-6"}, [
                            sbAdmin.form.mFormField("Refund Address to send all Reminaing Tokens and BTC", {id: 'shutdownAddress', 'placeholder': "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", required: true, }, vm.shutdownAddress),
                        ])
                    ]),


                    m("div", {class: "spacer1"}),

                    m("a[href='/admin/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                    sbAdmin.form.mSubmitBtn("Permanently Shutdown Bot", 'btn btn-danger'),
                    

                ]),

            ]),
        ]),



    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.botShutdownForm
