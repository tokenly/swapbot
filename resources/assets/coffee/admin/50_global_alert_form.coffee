# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
GlobalAlertPanel = require './10_global_alert_panel'
# ---- end references

ctrl = {}

ctrl.globalAlertForm = {}

# ### helpers #####################################

    

# ################################################

vm = ctrl.globalAlertForm.vm = do ()->
    vm = {}
    vm.init = ()->
        # view status
        vm.errorMessages = m.prop([])
        vm.formStatus = m.prop('active')
        vm.resourceId = m.prop('')

        # fields
        vm.alertContent = m.prop('')
        vm.alertStatus  = m.prop(false)

        # if there is an id, then load it from the api
        id = m.route.param('id')
        # load the settings info from the api
        sbAdmin.api.getAllSettings().then(
            (settingsData)->
                for k, setting of settingsData
                    if setting.name == 'globalAlert'
                        vm.alertContent(setting.value.content)
                        vm.alertStatus(setting.value.status)
                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )


        vm.save = (e)->
            e.preventDefault()

            attributes = {
                name: 'globalAlert'
                value: {
                    content: vm.alertContent()
                    status: vm.alertStatus()
                }
            }

            if vm.resourceId().length > 0
                # update existing settings
                apiCall = sbAdmin.api.updateSettings
                apiArgs = [vm.resourceId(), attributes]
            else
                # new settings
                apiCall = sbAdmin.api.newSettings
                apiArgs = [attributes]

            sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(()->
                # back to admin
                m.route('/admin/globalalert/saved')
                return
            )

        return
    return vm

ctrl.globalAlertForm.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    vm.init()
    return

ctrl.globalAlertForm.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("h2", "Global Swapbot Alert"),

                m("div", {class: "spacer1"}),

                sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                    sbAdmin.form.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            sbAdmin.form.mFormField("Alert Content", {type: 'textarea', id: 'alertContent', 'placeholder': "Type your alert message in markdown format here.", required: true,  }, vm.alertContent),
                        ]),
                    ]),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            sbAdmin.form.mCheckboxField("Alert is Active", {id: 'active', type: 'checkbox', }, vm.alertStatus),
                        ]),
                    ]),

                    m("div", {class: "spacer1"}),

                    sbAdmin.form.mSubmitBtn("Save Global Alert Settings"),
                    m("a[href='/admin']", {class: "btn btn-default pull-right", config: m.route}, "Cancel"),
                ]),

            ]),
        ]),



    ])
    return [sbAdmin.nav.buildNav(), GlobalAlertPanel.build(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.globalAlertForm
