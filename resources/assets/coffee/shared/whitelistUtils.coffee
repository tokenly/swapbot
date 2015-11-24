# swapRuleUtils functions

formatters   = require './formatters'
popover      = require './popover'

exports = {}

# #############################################
# local


# #############################################
# exports


exports.buildWhitelistSummaryProse = (bot)->
    MAX_LENGTH_TO_SHOW = 4

    whitelistAddresses = bot.resolvedWhitelistAddresses
    if whitelistAddresses? and whitelistAddresses.length > 0
        out = "Note: Swaps are only allowed from "
        if whitelistAddresses.length > MAX_LENGTH_TO_SHOW
            remainder = whitelistAddresses.length - MAX_LENGTH_TO_SHOW
            out += whitelistAddresses.slice(0, MAX_LENGTH_TO_SHOW).join(", ") + " and #{remainder} more"
        else
            if whitelistAddresses.length > 1
                out += whitelistAddresses.slice(0, -1).join(", ") + " and " + whitelistAddresses.slice(-1)
            else
                out += whitelistAddresses[0]

        return out

    return null

exports.buildMessageTextForPlaceOrder = (bot)->
    whitelistAddresses = bot.resolvedWhitelistAddresses
    if whitelistAddresses? and whitelistAddresses.length > 0

        isLong = whitelistAddresses.length > 20


        popoverConfig = {
            title: "Whitelisted Addresses"
            content: """
                <div#{if isLong then ' class="longPopoverDesc"'}>
                <p>You must send you deposit from one of the following addresses to complete this purchase:</p>
                <ul>
                    <li>#{bot.resolvedWhitelistAddresses.join('</li><li>')}</li>
                </ul>
                <p>Deposits sent from any other addresses will be refunded.</p>
                </div>
            """
        }
        return React.createElement('span', {className: "noUnderline"}, [
            "Note: Deposits must be sent from a whitelisted address",
            React.createElement('button', {className: 'button-question button-question-dark button-question-small', title: "About the Whitelisted Addresses", onClick: popover.buildOnClick(popoverConfig)}, ""),
        ])


    return null



module.exports = exports

