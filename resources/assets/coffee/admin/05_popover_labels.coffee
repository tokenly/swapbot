popoverLabels = {}


# ---------------------------------------------------------------------------------
# general form help

popoverLabels.blacklistedAddresses = {
    text: "Blacklisted Addresses"
    popover: {
        title: "About Blacklisted Addresses"
        content: """
            <p>Tokens received from blacklisted addresses do not trigger swaps and will not be refunded.</p>
            <p>Use blacklisted addresses to fill the Swapbot with additional inventory and BTC fuel.</p>
        """
    }
}

popoverLabels.whitelistedAddresses = {
    text: "Whitelisted Addresses"
    popover: {
        title: "About Whitelisted Addresses"
        content: """
            <p>If any whitelisted addresses are defined, then only swaps originating from these addresses will be processed.  All other swaps will be refunded.</p>
            <p>Use whitelisted addresses to create Swapbots that can only be used by specific people.</p>
        """
    }
}

# ---------------------------------------------------------------------------------
# swaps

popoverLabels.swapTypeChoice = (number, action)->
    return {
        text: "Type for #{action} Swap ##{number}"
        popover: {
            title: "About the Swap Type"
            content: """
                <p>Swapbot supports 3 types of swaps.</p>
                <ol class="ordered-list-unstyled">
                    <li>
                        <strong>Swaps By Rate</strong><br/>
                        <p>This is the simplest type of swap. Rate swaps buy and sell tokens at a price. Swaps of partial tokens are allowed.</p>
                    </li>
                    <li>
                        <strong>Swaps By Fixed Amounts</strong><br/>
                        <p>Fixed amounts accept an exact amount of tokens in and return an exact amount of tokens out. A user must send exactly the amount expected or more and will receive an exact amount of tokens back.  Any excess sent by the user is not refunded.</p>
                        <p>Users may send twice or three times as much as the expected amount and receive 2 or 3 times as many tokens in return.</p>
                    </li>
                    <li>
                        <strong>Swaps By USD Amount paid in BTC or Tokens</strong> (Sell Only)<br/>
                        <p>This type of swap accepts BTC and supported tokens only.  The tokens for sale are priced in a US dollar amount.  The amount of tokens sent in return is dependent on the current USD value of BTC when the transaction is received.</p>
                        <p>When purchasing with BTC, users are asked to send a small bit of extra BTC in order to adjust for market fluctuations.  Any excess BTC is returned to the user along with their tokens.</p>
                    </li>
                </ol>
            """
        }
    }

# ---------------------------------------------------------------------------------
# Sell by rate

popoverLabels.rateSellTokenToSell = {
    text: "Token to Sell"
    popover: {
        title: "About the Token to Sell"
        content: """
            <p>This is the type of token this Swapbot will send to the user.</p>
        """
    }
}

popoverLabels.rateSellAssetToReceive = {
    text: "Asset to Receive"
    class: 'control-label receives-label'
    popover: {
        title: "About the Asset to Receive"
        content: """
            <p>This is the type of token this Swapbot will receive from the user.</p>
            <p>This bot can receive BTC or any Counterparty asset.</p>
        """
    }
}

popoverLabels.rateSellPrice = {
    text: "Price"
    popover: {
        title: "About the Price"
        content: """
            <p>This is the cost the user will pay for 1 token.</p>
        """
    }
}

popoverLabels.rateSellMinimumSale = {
    text: "Minimum Sale"
    popover: {
        title: "About the Minimum Sale"
        content: """
            <p>This is the minimum amount the user is required to pay in order to complete a transaction.</p>
            <p>Deposits in BTC less than this minimum will be refunded minus a transaction fee.</p>
            <p>Deposits in other tokens less than this minimum will be refunded in their entirety.</p>
        """
    }
}

# ---------------------------------------------------------------------------------
# Buy by rate

popoverLabels.rateBuyTokenToBuy = {
    text: "Token to Buy",
    class: 'control-label receives-label'
    popover: {
        title: "About the Token to Buy"
        content: """
            <p>This is the type of token the user will send to the bot.</p>
        """
    }
}

popoverLabels.rateBuyAssetToPay = {
    text: "Asset to Pay"
    popover: {
        title: "About the Asset to Pay"
        content: """
            <p>This is the type of asset this Swapbot will send to the user.</p>
        """
    }
}

popoverLabels.rateBuyPurchasePrice = {
    text: "Purchase Price"
    popover: {
        title: "About the Purchase Price"
        content: """
            <p>This is the amount that this bot will return to the user for 1 token.</p>
        """
    }
}

popoverLabels.rateBuyMinimumSale = {
    text: "Minimum Purchase"
    popover: {
        title: "About the Minimum Purchase"
        content: """
            <p>This is the minimum amount the user is required to send in order to complete a transaction.</p>
            <p>Deposits in BTC less than this minimum will be refunded minus a transaction fee.</p>
            <p>Deposits in other tokens less than this minimum will be refunded in their entirety.</p>
        """
    }
}

# ---------------------------------------------------------------------------------
# Sell by fixed amounts

popoverLabels.fixedSellTokenToSell = {
    text: "Token to Sell"
    popover: {
        title: "About the Token to Sell"
        content: """
            <p>This is the type of token this Swapbot will send to the user.</p>
        """
    }
}

popoverLabels.fixedSellAmountToSell = {
    text: "Amount to Sell"
    popover: {
        title: "About the Amount to Sell"
        content: """
            <p>This is the amount of the token this Swapbot will send to the user.</p>
            <p>The user will receive this amount or an exact multiple of this amount if they send an exact multiple of the Amount to Receive.</p>
        """
    }
}

popoverLabels.fixedSellAssetToReceive = {
    text: "Asset to Receive"
    class: 'control-label receives-label'
    popover: {
        title: "About the Asset to Receive"
        content: """
            <p>This is the type of token the user will send to the bot.</p>
            <p>This bot can receive BTC or any Counterparty asset.</p>
        """
    }
}

popoverLabels.fixedSellAmountToReceive = {
    text: "Amount to Receive"
    class: 'control-label'
    popover: {
        title: "About the Amount to Receive"
        content: """
            <p>This is the amount of the token the user will send to the bot.</p>
            <p>The user should send this amount or an exact multiple of this amount.</p>
        """
    }
}

# ---------------------------------------------------------------------------------
# Buy by fixed amounts

popoverLabels.fixedBuyTokenToBuy = {
    text: "Token to Buy"
    class: 'control-label receives-label'
    popover: {
        title: "About the Token to Buy"
        content: """
            <p>This is the type of token the user will send to the bot.</p>
        """
    }
}

popoverLabels.fixedBuyAmountToBuy = {
    text: "Amount to Buy"
    class: 'control-label'
    popover: {
        title: "About the Amount to Buy"
        content: """
            <p>This is the amount of the token the user will send to the bot.</p>
            <p>The user should send this amount or an exact multiple of this amount.</p>
        """
    }
}

popoverLabels.fixedBuyAssetToPay = {
    text: "Asset to Pay"
    popover: {
        title: "About the Asset to Pay"
        content: """
            <p>This is the type of token this Swapbot will send to the user.</p>
            <p>This bot can send BTC or any Counterparty asset.</p>
        """
    }
}

popoverLabels.fixedBuyAmountToPay = {
    text: "Amount to Pay"
    popover: {
        title: "About the Amount to Pay"
        content: """
            <p>This is the amount of the token this Swapbot will send to the user.</p>
            <p>The user will receive this amount or an exact multiple of this amount if they send an exact multiple of the Amount to Buy.</p>
        """
    }
}


# ---------------------------------------------------------------------------------
# Sell by fiat

popoverLabels.fiatSellReceivesAsset = {
    text: "Receives"
    class: 'control-label receives-label'
    popover: {
        title: "About the Asset to Receive"
        content: """
            <p>This Swapbot will receive BTC or tokens and get a quote in USD at the current market rate.</p>
            <p>Not all tokens are supported.</p>
        """
    }
}
popoverLabels.fiatSellSendsAsset = {
    text: "Token to Sell"
    popover: {
        title: "About the Token to Sell"
        content: """
            <p>This is the type of token this Swapbot will send to the user.</p>
        """
    }
}
popoverLabels.fiatSellPrice = {
    text: "Price"
    popover: {
        title: "About the Price"
        content: """
            <p>This is the cost in USD that the user will pay for 1 token.</p>
            <p>When the deposit transaction is received, this Swapbot will get a quote in USD at the current market rate.</p>
        """
    }
}
popoverLabels.fiatSellMinimumSale = {
    text: "Minimum Sale"
    popover: {
        title: "About the Minimum Sale"
        content: """
            <p>This is the minimum amount of your Token that the user must purchase to complete a swap.</p>
            <p>Deposits in BTC less than this minimum will be refunded minus a transaction fee.</p>
        """
    }
}
popoverLabels.fiatSellIsDivisible = {
    text: "Divisible"
    popover: {
        title: "About Token is Divisible"
        content: """
            <p>If this token is marked as divisible, then an exact amount of this token will be sent to the user based on the market rate of the BTC or tokens received.</p>
            <p>If the token is not divisible, then the number of tokens purchased will be rounded down to the nearest whole number.  If the customer deposited BTC, then the excess BTC will be sent back as change.  This swapbot will ask the user to send an additional small BTC buffer to account for market fluctuations when selling tokens for BTC.</p>
        """
    }
}



# ---------------------------------------------------------------------------------
popoverLabels.urlSlug = {
    text: "Bot URL Slug"
    popover: {
        title: "About The Bot URL Slug"
        content: """
            <p>The bot URL slug can contain only lowercase letters, numbers and dashes.  It should be at least 8 characters long and not more than 80 characters long.</p>
        """
    }
}

# ---------------------------------------------------------------------------------
popoverLabels.buildAdvancedSwapRuleType = (offset)->
    return {
        text: "Type for Rule #{offset+1}"
        popover: {
            title: "About The Rule Type"
            content: """
                <p>Swaps may have different types of advanced rules in the future.  Right now the only type of advanced rule available is the Bulk Discount rule.</p>
            """
        }
    }

popoverLabels.swapRuleName = {
        text: "Rule Name"
        popover: {
            title: "About The Rule Name"
            content: """
                <p>Give this advanced swap rule a name so you can assign it to one or more swaps.</p>
                <p>This name is for your use. Customers of the bot using the web interface will not see it.</p>
            """
        }
    }

popoverLabels.advancedSwapBulkDiscounts = {
        text: "Bulk Discounts"
        popover: {
            title: "About Bulk Discounts"
            content: """
                <p>The <strong>Minimum Order</strong> is the amount the user needs to purchase to qualify for a discount.  The <strong>Percent Discount</strong> is expressed as decimal number between 0 and 100.</p>
                <p>When multiple discounts are available, only the single best discount is applied to a swap.</p>
                <p>Bulk discounts may only be applied to selling swaps.</p>
            """
        }
    }




# ---------------------------------------------------------------------------------
module.exports = popoverLabels
