# quoteUtils functions

QuotebotStore = require '../stores/QuotebotStore'
swapbot = swapbot or {}; swapbot.formatters = require '../../shared/formatters'


exports = {}

SATOSHI = 100000000

# #############################################
# local

resolveRate = (asset, fiat, currentQuotes)->
    if asset == 'BTC'
        # console.log "using BTC rate of #{currentQuotes["bitcoinAverage.USD:BTC"]?.last}"
        return currentQuotes["bitcoinAverage.USD:BTC"]?.last

    btcRate = resolveRate('BTC', 'USD', currentQuotes)
    if not btcRate then return 0

    assetQuote = currentQuotes["poloniex.BTC:#{asset}"]
    if not assetQuote then return 0

    # the assetRate is the lowest of:
    #   1) the current price
    #   2) the 24 hour average price
    assetRate = assetQuote.last
    if not assetRate then return 0
    lowestAssetRateAvg = assetQuote.lastAvg
    if not lowestAssetRateAvg then return 0
    assetRate = Math.min(assetRate, lowestAssetRateAvg)

    if assetQuote.inSatoshis
        assetRate = assetRate / SATOSHI

    return assetRate * btcRate



# #############################################
# exports

exports.fiatQuoteSuffix = (swapConfig, amount, asset)->
    return exports.fiatQuoteSuffixFromQuotes(swapConfig, amount, asset, QuotebotStore.getCurrentQuotes())

exports.fiatQuoteSuffixFromQuotes = (swapConfig, amount, asset, currentQuotes)->
    return '' if swapConfig.strategy != 'fiat'

    fiatRate = exports.resolveFiatPriceFromQuotes(asset, 'USD', currentQuotes)
    if not fiatRate then return ''

    fiatAmount = fiatRate * amount
    return ' ('+swapbot.formatters.formatFiatCurrency(fiatAmount)+')'

exports.resolveFiatPriceFromQuotes = (asset, fiat, currentQuotes)->
    return resolveRate(asset, fiat, currentQuotes)


# #############################################
module.exports = exports

