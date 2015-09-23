# eventMessageUtils functions

swapbot = swapbot or {}; swapbot.formatters = require './formatters'


exports = {}

# #############################################
# local


# #############################################

exports.buildTransactionLinkHref = buildTransactionLinkHref = (txid)->
    return "https://chain.so/tx/BTC/#{txid}"

exports.buildTransactionLinkElement = buildTransactionLinkElement = (txid, linkContents=null, keyId=null)->
    return null if not txid?
    linkContents = txid if not linkContents?

    return React.createElement('a', {key: keyId, href: buildTransactionLinkHref(txid), target: '_blank', className: 'externalLink'}, linkContents)

exports.buildSwapStatusMessageElement = (swap, bot)->
    switch swap.state
        when 'sent', 'refunded'
            return React.createElement('span', {}, [
                "Confirming  ",
                buildTransactionLinkElement(swap.txidOut, (if swap.state == 'refunded' then 'refund' else 'delivery'), "le-#{swap.id}"),
                " with #{swapbot.formatters.confirmationsProse(swap.confirmationsOut)}."
            ])
        when 'outofstock'
            return React.createElement('span', {}, [
                'This swap is out of stock. '
                buildTransactionLinkElement(swap.txidIn, ' Receiving tokens ', "le-#{swap.id}"),
                " and waiting to send #{swap.quantityOut} #{swap.assetOut}."
            ]);
        when 'outoffuel'
            return React.createElement('span', {}, [
                'This swap is out of fuel. '
                buildTransactionLinkElement(swap.txidIn, ' Receiving tokens ', "le-#{swap.id}"),
                " and waiting to send #{swap.quantityOut} #{swap.assetOut}."
            ]);
    
    return React.createElement('span', {}, [
        "Waiting for ",
        buildTransactionLinkElement(swap.txidIn, swapbot.formatters.confirmationsProse(bot.confirmationsRequired), "le-#{swap.id}"),
        " to send #{swap.quantityOut} #{swap.assetOut}."
    ]);

exports.fullSwapSummary = (swap, bot)->
    return React.createElement('span', {}, [
        "You deposited #{swapbot.formatters.formatCurrency(swap.quantityIn)} #{swap.assetIn} and we delivered #{swapbot.formatters.formatCurrency(swap.quantityOut)} #{swap.assetOut} to #{swap.destination}.",
    ])

module.exports = exports

