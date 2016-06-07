# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
GlobalAlertSubscriber = require './10_global_alert_subscriber'
marked = require '../../../../node_modules/marked'
# ---- end references

# globalAlert functions
globalAlert = {}


alertData = null

alertDataChanged = (d1, d2)->
    if (d1?.status != d2?.status) then return true
    if (d1?.content != d2?.content) then return true
    return false

globalAlert.init = ()->
    GlobalAlertSubscriber.addChangeListener (newAlertData)->
        oldAlertData = alertData
        alertData = newAlertData
        if alertDataChanged(oldAlertData, alertData)
            m.redraw(true)
        return
    return

globalAlert.build = ()->
    if not alertData? or not alertData.status
        return null

    html = marked(alertData.content or '')

    return  m("div", { class: "container-fluid globalalert-container", style: {}}, [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12 col-lg-10 col-lg-offset-1"}, [
                    m("div", {class: "panel panel-danger"}, [
                        m("div", {class: 'panel-heading'}, [
                            m("h4", {class: 'panel-title'}, 
                                "Attention"
                            ),
                        ]),
                        m("div", {class: 'panel-body'}, m.trust(html)),
                    ]),
                ]),
            ])
        ]);


module.exports = globalAlert
