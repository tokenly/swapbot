# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
GlobalAlertPanel = require './10_global_alert_panel'
# ---- end references

ctrl = {}

ctrl.settingsForm = {}

# ### helpers #####################################

    

# ################################################

vm = ctrl.settingsForm.vm = do ()->
    vm = {}
    vm.init = ()->
        # view status
        vm.errorMessages = m.prop([])
        vm.formStatus = m.prop('active')
        vm.resourceId = m.prop('')

        # fields
        vm.name         = m.prop('')
        vm.value        = m.prop('')

        # if there is an id, then load it from the api
        id = m.route.param('id')
        if id != 'new'
            # load the settings info from the api
            sbAdmin.api.getSettings(id).then(
                (settingsData)->
                    vm.resourceId(settingsData.id)

                    vm.name(settingsData.name)

                    v = settingsData.value
                    # console.log "typeof v=",typeof v
                    if v? and typeof v == 'object'
                        # console.log "stringify"
                        v = window.JSON.stringify(v, null, 2)
                    # console.log "v=",v
                    vm.value(v)

                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )


        vm.save = (e)->
            e.preventDefault()

            attributes = {
                name: vm.name()
                value: vm.value()
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
                # back to settings
                m.route('/admin/settings')
                return
            )

        return
    return vm

ctrl.settingsForm.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    vm.init()
    return

ctrl.settingsForm.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("h2", if vm.resourceId() then "Edit Setting #{vm.name()}" else "Create New Settings"),

                m("div", {class: "spacer1"}),

                # m("form", {onsubmit: vm.save, }, [
                sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                    sbAdmin.form.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            sbAdmin.form.mFormField("Name", {id: 'name', 'placeholder': "Name", required: true, }, vm.name),
                        ]),
                    ]),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-12"}, [
                            sbAdmin.form.mFormField("Value",  {type: 'textarea', id: 'value', 'placeholder': "{}", style: {height: '300px'}, required: true, }, vm.value),
                        ]),
                    ]),

                    m("div", {class: "spacer1"}),

                    sbAdmin.form.mSubmitBtn("Save Settings"),
                    m("a[href='/admin/settings']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                    

                ]),

            ]),
        ]),



    ])
    return [sbAdmin.nav.buildNav(), GlobalAlertPanel.build(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.settingsForm
