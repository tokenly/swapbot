# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.pusherutils = require './10_pusher_utils'
swapbot = swapbot or {}; swapbot.addressUtils = require '../shared/addressUtils'
# ---- end references

ctrl = {}

ctrl.swapEvents = {}

# ################################################


buildMLevel = (levelNumber)->
    switch levelNumber
        when 100 then return m('span', {class: "label label-default debug"}, "Debug")
        when 200 then return m('span', {class: "label label-info info"}, "Info")
        when 250 then return m('span', {class: "label label-primary primary"}, "Notice")
        when 300 then return m('span', {class: "label label-warning warning"}, "Warning")
        when 400 then return m('span', {class: "label label-danger danger"}, "Error")
        when 500 then return m('span', {class: "label label-danger danger"}, "Critical")
        when 550 then return m('span', {class: "label label-danger danger"}, "Alert")
        when 600 then return m('span', {class: "label label-danger danger"}, "Emergency")
    return m('span', {class: "label label-danger danger"}, "Code #{levelNumber}")

handleBotEventMessage = (data)->
    console.log "handleBotEventMessage data=",data
    if appendBotEventMessage(data)
        m.redraw(true)
    return

appendBotEventMessage = (data, reverse=true)->
    anyAppended = false
    if data?.event?.msg or data?.message
        if data.swapUuid == vm.swapId
            if reverse
                vm.swapEvents().unshift(data)
            else
                vm.swapEvents().push(data)
            anyAppended = true
    return anyAppended


# ################################################

vm = ctrl.swapEvents.vm = do ()->
    vm = {}
    vm.init = ()->
        vm.showDebug = false

        vm.pusherClients = []
        vm.errorMessages = m.prop([])
        vm.user = m.prop(sbAdmin.auth.getUser())
        vm.swapId = m.route.param('id')

        # init
        vm.swap = m.prop(null)
        vm.swapEvents = m.prop([])

        # load swap
        sbAdmin.api.getSwap(vm.swapId).then(
            (swapData)->
                swap = swapData
                vm.swap(swap)

                # also get the swap events
                bot_id = swap.botUuid
                sbAdmin.api.getBotEvents(bot_id).then(
                    (apiResponse)->
                        for data in apiResponse
                            appendBotEventMessage(data, false)
                        return
                    , (errorResponse)->
                        vm.errorMessages(errorResponse.errors)
                        return
                )

                vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_#{bot_id}", handleBotEventMessage))

                return
            , (errorResponse)->
                vm.errorMessages(errorResponse.errors)
                return
        )



        return


    vm.toggleDebugView = (e)->
        e.preventDefault()
        vm.showDebug = !vm.showDebug
        return

    return vm


ctrl.swapEvents.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # bind unload event
    this.onunload = (e)->
        for pusherClient in vm.pusherClients
            sbAdmin.pusherutils.closePusherChanel(pusherClient)

    # init
    vm.init()

    return

ctrl.swapEvents.view = ()->
    swap = vm.swap()
    if not swap
        mEl = m("div", [
            m("h2", "Swap not found"),
            sbAdmin.form.mAlerts(vm.errorMessages),
        ])
    else
        botAaddress = swapbot.addressUtils.publicBotAddress(swap.botUsername, swap.botUuid, window.location)
                    
        mEl = m("div", [
            m("h4", "Swap Events for swap #{swap.id}"),
            m("p", {}, ["This swap belongs to the bot ",m("a[href='#{botAaddress}']", {target: "_blank", class: "",}, swap.botName),]),

            m("div", {class: "spacer1"}),

            sbAdmin.form.mAlerts(vm.errorMessages),
            
            m("div", {class: "bot-events"}, [
                m("div", {class: "pulse-spinner pull-right"}, [m("div", {class: "rect1",}),m("div", {class: "rect2",}),m("div", {class: "rect3",}),m("div", {class: "rect4",}),m("div", {class: "rect5",}),]),
                m("h3", "Events"),
                if vm.swapEvents().length == 0 then m("div", {class:"no-events", }, "No Events Yet") else null,
                m("ul", {class: "list-unstyled striped-list bot-list event-list event-list-standalone"}, [
                    vm.swapEvents().map (botEventObj)->
                        if not vm.showDebug and botEventObj.level <= 100
                            return
                        dateObj = window.moment(botEventObj.createdAt)
                        return m("li", {class: "bot-list-entry event"}, [
                            m("div", {class: "labelWrapper"}, buildMLevel(botEventObj.level)),
                            m("span", {class: "date", title: dateObj.format('MMMM Do YYYY, h:mm:ss a')}, dateObj.format('MMM D h:mm a')),
                            m("span", {class: "msg"}, (botEventObj.message or botEventObj.event?.msg)),
                        ])
                ]),
                m("div", {class: "pull-right"}, [
                    m("a[href='#show-debug']", {onclick: vm.toggleDebugView, class: "btn #{if vm.showDebug then 'btn-warning' else 'btn-default'} btn-xs", style: {"margin-right": "16px",} }, [
                        if vm.showDebug then "Hide Debug" else "Show Debug"
                    ]),
                ]),
            ]),
        ])

    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.swapEvents
