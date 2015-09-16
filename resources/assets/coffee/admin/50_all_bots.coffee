# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.robohashUtils = require './10_robohash_utils'
swapbot = swapbot or {}; swapbot.addressUtils = require '../shared/addressUtils'
# ---- end references

ctrl = {}
ctrl.allbots = {}

# ################################################

vm = ctrl.allbots.vm = do ()->
    vm = {}
    vm.init = ()->
        vm.user = m.prop(sbAdmin.auth.getUser())

        # init
        vm.bots = m.prop([])
        vm.botsRefreshing = m.prop('true')

        # swapbots
        vm.refreshBots()

        return

    vm.refreshBotsFn = (e)->
        e.preventDefault()
        vm.refreshBots()
        return

    vm.refreshBots = ()->
        vm.botsRefreshing('true')

        m.redraw(true)
        sbAdmin.api.getBotsForAllUsers().then (botsList)->
            vm.bots(botsList)
            vm.botsRefreshing(false)
            return

        return

    return vm


ctrl.allbots.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # init
    vm.init()

    return

    removeImgFn = (e)->
        imageIdProp(null)
        imageDetailsProp(null)
        e.preventDefault()
        return

ctrl.allbots.view = ()->
    mEl = m("div", [
        m("h2", "All Swapbots"),

        m("div", {class: "spacer1"}),
        
        m("p", {class: "pull-right"}, [m("a[href='#refresh']", {onclick: vm.refreshBotsFn}, [m("span", {class: "glyphicon glyphicon-refresh", title: "Refresh"}, ''),' Refresh'])]),
        m("p", {class: ""}, ["Here is a list of all Swapbots.",]),

        m("div", { class: "row"}, [
            m("div", {class: "col-md-12"}, [
                m("table", {class: "striped-table bot-table #{if vm.botsRefreshing() then 'refreshing' else ''}"}, [
                    m('thead', {}, [
                        m('tr', {}, [
                            m('th', {}, 'Bot Name'),
                            m('th', {}, 'Admin Link'),
                            m('th', {}, 'State'),
                            m('th', {}, 'Owner'),
                        ]),
                    ]),
                    vm.bots().map((bot)->
                        address = swapbot.addressUtils.publicBotAddress(bot.username, bot.id, window.location)
                        return m("tr", {}, [
                            m("td", {}, [
                                if bot.hash.length then m("a[href='#{address}']", {target: "_blank"}, [sbAdmin.robohashUtils.img(bot.hash, 'tinyRoboHead')]) else m('div', {class: 'emptyRoboHead'}, ''),
                                m("a[href='#{address}']", {target: "_blank", class: "",}, "#{bot.name}"),
                            ]),
                            m("td", {}, [
                                m("a[href='/admin/view/bot/#{bot.id}']", {class: "", config: m.route}, "Admin"),
                            ]),
                            m("td", {}, bot.state),
                            m("td", {}, bot.username),
                        ])
                    )
                ]),
            ]),
        ]),
            

        m("div", {class: "spacer1"}),

        
    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######

module.exports = ctrl.allbots