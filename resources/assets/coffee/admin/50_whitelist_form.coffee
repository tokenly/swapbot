# ---- begin references
SwapbotAPI    = require './10_api_functions'
AdminForm     = require './10_form_helpers'
SwapbotAuth   = require './10_auth_functions'
CSVFileHandler= require './10_CSVFileHandler'
SwapbotNav    = require './10_nav'
popoverLabels = require './05_popover_labels'
# ---- end references

$ = window.jQuery

ctrl = {}

ctrl.whitelistForm = {}




# ################################################

vm = ctrl.whitelistForm.vm = do ()->

    vm = {}
    vm.init = ()->
        # view status
        vm.errorMessages = m.prop([])
        vm.formStatus = m.prop('active')
        vm.resourceId = m.prop('')

        # fields
        vm.name = m.prop('')
        vm.data = m.prop(null)
        vm.fileUploadDetails = m.prop(false)

        id = m.route.param('id')
        vm.isNew = (id == 'new')
        if !vm.isNew
            # load the bot info from the api
            SwapbotAPI.getWhitelist(id).then(
                (whitelistData)->
                    vm.resourceId(whitelistData.id)
                    vm.name(whitelistData.name)

                    if whitelistData.data
                        inflatedData = CSVFileHandler.unflattenFlatData(whitelistData.data, "Whitelisted Address")
                        CSVFileHandler.initExistingFile(vm.fileUploadDetails, inflatedData)
                        vm.data(inflatedData)

                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )

        vm.save = (e)->
            e.preventDefault()
            attributes = {
                name: vm.name()
                data: CSVFileHandler.flattenCSVDataRows(vm.data())
            }

            if vm.resourceId().length > 0
                # update existing bot
                apiCall = SwapbotAPI.updateWhitelist
                apiArgs = [vm.resourceId(), attributes]
            else
                # new bot
                apiCall = SwapbotAPI.newWhitelist
                apiArgs = [attributes]

            AdminForm.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then((apiResponse)->
                if vm.isNew
                    # console.log "apiResponse=",apiResponse
                    whitelistId = apiResponse.id
                else
                    whitelistId = vm.resourceId()

                m.route("/admin/whitelists")
                return
            , (error)->
                # scroll to the top
                $('html, body').animate({scrollTop: 0}, 750)
            )

        return
    return vm

ctrl.whitelistForm.controller = ()->
    # require login
    SwapbotAuth.redirectIfNotLoggedIn()

    vm.init()
    return

ctrl.whitelistForm.view = ()->
    mEl = m("div", [
        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [

                m("div", { class: "row"}, [
                    m("div", {class: "col-md-10"}, [
                        m("h2", if vm.resourceId() then "Edit Whitelist #{vm.name()}" else "Create a New Whitelist"),
                    ]),
                ]),


                m("div", {class: "spacer1"}),

                # m("form", {onsubmit: vm.save, }, [
                AdminForm.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                    AdminForm.mAlerts(vm.errorMessages),

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-5"}, [
                            AdminForm.mFormField("Whitelist Name", {id: 'name', 'placeholder': "Whitelist Name", required: true, }, vm.name),
                        ]),
                    ]),
                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-8"}, [
                            CSVFileHandler.mDataUpload("Upload CSV File", {id: 'UploadData',}, vm.data, vm.fileUploadDetails),
                        ]),
                    ]),


                    # -------------------------------------------------------------------------------------------------------------------------------------------


                    m("div", {class: "spacer3"}),

                    m("a[href='/admin/whitelists']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                    AdminForm.mSubmitBtn("Save Whitelist"),
                    m("a[href='/admin/delete/whitelist/#{vm.resourceId()}']", {class: "btn btn-warning ", config: m.route, style: {'margin-left': '24px'}}, "Delete Whitelist"),


                ]),

            ]),
        ]),



    ])
    return [SwapbotNav.buildNav(), SwapbotNav.buildInContainer(mEl)]


######
module.exports = ctrl.whitelistForm
