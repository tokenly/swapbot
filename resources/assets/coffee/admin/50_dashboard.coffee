# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
sbAdmin = sbAdmin or {}; sbAdmin.auth = require './10_auth_functions'
sbAdmin = sbAdmin or {}; sbAdmin.nav = require './10_nav'
sbAdmin = sbAdmin or {}; sbAdmin.robohashUtils = require './10_robohash_utils'
Stateutils      = require './10_state_utils'
BotPaymentUtils = require './10_bot_payment_utils'
AddressUtils    = require '../shared/addressUtils'

# ---- end references

ctrl = {}

ctrl.dashboard = {}

# ### helpers #####################################

listSwapbots = ()->
    sbAdmin.api.getBots().then (botsList)->
        return m.prop(botsList)

# ################################################

vm = ctrl.dashboard.vm = do ()->
    vm = {}
    vm.init = ()->
        vm.user = m.prop(sbAdmin.auth.getUser())

        vm.paymentStatuses = {}

        # swapbots
        vm.bots = m.prop([])
        sbAdmin.api.getAllBots().then (botsList)->
            vm.bots(botsList)
            # m.redraw(true)
            return

        return
    return vm


ctrl.dashboard.controller = ()->
    # require login
    sbAdmin.auth.redirectIfNotLoggedIn()

    # init
    vm.init()

    return

ctrl.dashboard.view = ()->
    if vm.bots().length
        sortedBots = vm.bots().slice(0)
        sortedBots.sort (a,b)->
            return (if b.name.toUpperCase() < a.name.toUpperCase() then 1 else -1)

        botsListEl = [
            m("p", {class: ""}, "Here is a list of your Swapbots:"),

            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [
                    m("table", {class: "table table-striped bot-table"}, [
                        m("thead", {}, [
                            m("tr", {}, [
                                m("th", {}, "Bot Admin"),
                                m("th", {}, "Public Link"),
                                m("th", {}, "Status"),
                                m("th", {}, "Next Payment Due"),
                                m("th", {}, ""),
                            ]),
                        ]),
                        m("tbody", {}, 
                            sortedBots.map((bot)->
                                BotPaymentUtils.buildFormattedBotDueDateTextFromBot(bot, vm.paymentStatuses)

                                return m("tr", {}, [
                                            m("td", {}, [
                                                if bot.hash.length then m("a[href='/admin/view/bot/#{bot.id}']", {config: m.route}, sbAdmin.robohashUtils.img(bot.hash, 'tinyRoboHead')) else m('div', {class: 'emptyRoboHead'}, ''),
                                                m("a[href='/admin/view/bot/#{bot.id}']", {class: "", config: m.route}, "#{bot.name}"),
                                            ]),
                                            m("td", {}, [
                                                m("a[href='#{AddressUtils.publicBotHrefFromBot(bot)}']", {target: "_blank", class: "",}, "Public Bot Link"),
                                            ]),
                                            m("td", {}, Stateutils.buildStateSpan(bot.state)),
                                            m("td", {}, vm.paymentStatuses[bot.id].resultText()),
                                            m("td", {}, [
                                                m("a[href='/admin/edit/bot/#{bot.id}']", {class: "dashboard-edit-link", config: m.route}, [
                                                    m("span", {class: "glyphicon glyphicon-edit", title: "Edit Swapbot #{bot.name}"}, ''),
                                                    " Edit",
                                                ]),
                                            ]),
                                        ])
                            ),
                        ),
                    ]),
                ]),
            ]),
        ]
    else
        botsListEl = 
        [
            m("p", {class: ""}, [
                "You don't have any swapbots yet.  After you create some swapbots, they will be listed here.",
            ]),
            m("p", {class: ""}, [
                "For some help, check out the ", 
                m("a[href='https://www.youtube.com/watch?v=MCdFHx3yTfE']", {class: "", }, "Swapbot Admin Tutorial"),
                ".", 
            ]),
            m("div", {class: "spacer1"}),

        ]

    mEl = m("div", [
        m("h2", "Hi #{vm.user().name}."),
        m("h3", "Welcome to your Swapbot Control Panel."),

        m("div", {class: "spacer1"}),

        botsListEl,

        m("div", {class: "spacer1"}),

        m("a[href='/admin/edit/bot/new']", {class: "btn btn-primary", config: m.route}, "Create a new Swapbot"),
        
    ])
    return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


######
module.exports = ctrl.dashboard
