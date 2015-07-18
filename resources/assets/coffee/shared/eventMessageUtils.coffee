# eventMessageUtils functions
swapbot = {} if not swapbot?

swapbot.eventMessageUtils = do ()->
    exports = {}

    # #############################################
    # local


    # #############################################

    exports.buildTransactionLinkHref = buildTransactionLinkHref = (txid)->
        return "https://chain.so/tx/BTC/#{txid}"

    exports.buildTransactionLinkElement = buildTransactionLinkElement = (txid, linkContents=null)->
        return null if not txid?
        linkContents = txid if not linkContents?

        return React.createElement('a', {href: buildTransactionLinkHref(txid), target: '_blank', className: 'externalLink'}, linkContents)

    exports.buildSwapStatusMessageElement = (swap, bot)->
        switch swap.state
            when 'sent', 'refunded'
                return React.createElement('span', {}, [
                    "Confirming  ",
                    buildTransactionLinkElement(swap.txidOut, (if swap.state == 'refunded' then 'refund' else 'delivery')),
                    " with #{swapbot.formatters.confirmationsProse(swap.confirmationsOut)}."
                ])
        
        return React.createElement('span', {}, [
            "Waiting for ",
            buildTransactionLinkElement(swap.txidIn, swapbot.formatters.confirmationsProse(bot.confirmationsRequired)),
            " to send #{swap.quantityOut} #{swap.assetOut}."
        ]);

    exports.fullSwapSummary = (swap, bot)->
        return React.createElement('span', {}, [
            "You deposited #{swapbot.formatters.formatCurrency(swap.quantityIn)} #{swap.assetIn} and we delivered #{swapbot.formatters.formatCurrency(swap.quantityOut)} #{swap.assetOut} to #{swap.destination}.",
        ])

    return exports

