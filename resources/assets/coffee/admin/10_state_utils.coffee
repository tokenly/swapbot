# stateutils functions
stateutils = {}

stateutils.buildStateSpan = (stateValue)->
    switch stateValue
        when 'brandnew'
            return m("span", {class: 'no'}, stateutils.buildStateLabel(stateValue))
        when 'lowfuel'
            return m("span", {class: 'no'}, stateutils.buildStateLabel(stateValue))
        when 'active'
            return m("span", {class: 'yes'}, stateutils.buildStateLabel(stateValue))
        else
            return m("span", {class: 'no'}, stateutils.buildStateLabel(stateValue))
    return


stateutils.buildStateLabel = (stateValue)->
    switch stateValue
        when 'brandnew'
            return "Waiting for Payment"
        when 'lowfuel'
            return "Low Fuel"
        when 'active'
            return "Active"
        else
            return "Inactive"
    

stateutils.buildStateDetails = (stateValue, planDetails, paymentAddress, botAddress)->
    details = {
        label: ''
        subtitle: ''
        class: ''
    }

    switch stateValue
        when 'brandnew'
            initialPaymentsCount = 20

            # if planDetails
            #     initialPaymentsAmount = planDetails.txFee * initialPaymentsCount
            #     amount = planDetails.creationFee + planDetails.initialFuel + initialPaymentsAmount
            #     creationFee = planDetails.creationFee
            #     initialFuel = planDetails.initialFuel
            # else
            #     initialPaymentsAmount = '[unknown]'
            #     amount = '[unknown]'
            #     creationFee = '[unknown]'
            #     initialFuel = '[unknown]'
            details.label = stateutils.buildStateLabel(stateValue)
            details.subtitle = m('div', {}, [
                    m('div', "This is a new swapbot and needs to be paid to be activated.  Please send a monthly payment to #{paymentAddress}."),
                    m('br'),
                    "This swapbot is low on BTC fuel.  To load your bot with bitcoin fuel or token inventory please add your addresses to the Address Blacklist below.  ",
                    m('strong', "Do not send directly from an exchange to your bot under any circumstances."),
                    "Instead, send from the exchange to your Blacklisted Address, then to your bot at #{botAddress}",
                    m('br'),
                    m('div', {class: 'note'}, "Note: If you've just added fuel or paid to set up this Swapbot, you can ignore this message.")
                ])


            details.class = "panel-warning inactive new"
        when 'lowfuel'
            details.label = stateutils.buildStateLabel(stateValue)
            details.subtitle = m('div', {}, [
                    "This swapbot is low on BTC fuel.  To load your bot with bitcoin fuel or token inventory please add your addresses to the Address Blacklist below.  ",
                    m('strong', "Do not send directly from an exchange to your bot under any circumstances."),
                    "Instead, send from the exchange to your Blacklisted Address, then to your bot at #{botAddress}",
                    m('br'),
                    m('div', {class: 'note'}, "Note: If you've just added fuel or paid to set up this Swapbot, you can ignore this message.")
                ])
            details.class = "panel-warning inactive lowfuel"
        when 'active'
            details.label = stateutils.buildStateLabel(stateValue)
            details.subtitle = "This swapbot is up and running.  All is well."
            details.class = "panel-success active"
        else
            details.label = stateutils.buildStateLabel(stateValue)
            details.subtitle = "This swapbot is inactive.  Swaps are not being processed."
            details.class = "panel-danger inactive deactivated"
    
    return details

stateutils.buildStateDisplay = (details)->
    return m("div", {class: "panel #{details.class}"}, [
        m("div", {class: 'panel-heading'}, [
            m("h3", {class: 'panel-title'}, details.label),
        ]),
        m("div", {class: 'panel-body'}, details.subtitle),
    ])

module.exports = stateutils
