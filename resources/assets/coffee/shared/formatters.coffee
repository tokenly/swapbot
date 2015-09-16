# formatters functions


exports = {}


SATOSHI = 100000000

# #############################################
# local

# #############################################
# exports

exports.formatConfirmations = (confirmations)->
    return 0 if not confirmations?
    return window.numeral(confirmations).format('0')

exports.confirmationsProse = (confirmations)->
    return "#{exports.formatConfirmations(confirmations)} #{exports.confirmationsWord(confirmations)}"

exports.confirmationsWord = (confirmations)->
    return "confirmation#{if confirmations == 1 then '' else 's'}"

exports.satoshisToValue = (amount, currencyPostfix='BTC') ->
    return exports.formatCurrency(amount / SATOSHI, currencyPostfix)

isZero = (value)->
    if not value? or value.length == 0 or value == 0
        return true
    return false

exports.isZero = isZero

exports.isNotZero = (value)-> return not isZero(value)

exports.formatCurrencyWithForcedZero = (value, currencyPostfix='') ->
    return exports.formatCurrency((if isZero(value) then 0 else value), currencyPostfix)

exports.formatCurrency = (value, currencyPostfix='') ->
    if not value? or isNaN(value) then return ''

    decimalText = window.numeral(value).format('0,0.[00000000]')

    if value > 0 and value < 0.0001
        # 40 satoshis (0.00000040) BTC
        satoshisPrefix = window.numeral(value * SATOSHI).format('0')+' satoshis'
        valueText = "#{satoshisPrefix} (#{decimalText})"
    else
        valueText = decimalText

    return valueText + (if currencyPostfix?.length then ' '+currencyPostfix else '')

exports.formatCurrencyAsNumber = (value) ->
    if not value? or isNaN(value) then return '0'
    return window.numeral(value).format('0.[00000000]')

exports.formatFiatCurrency = (value, currencyPrefix='$')->
    if not value? or isNaN(value) then return ''
    formattedCurrencyString = window.numeral(value).format('0,0.00')
    prefix = ''
    if formattedCurrencyString == '0.00'
        prefix = 'less than '
        formattedCurrencyString = '0.01'
    return prefix+(if currencyPrefix?.length then currencyPrefix else '')+(formattedCurrencyString)

exports.formatArbitraryPrecisionFiatCurrency = (value, currencyPrefix='$', formatString='0,0.00[000000]')->
    if not value? or isNaN(value) then return ''
    formattedCurrencyString = window.numeral(value).format(formatString)
    prefix = ''
    return prefix+(if currencyPrefix?.length then currencyPrefix else '')+(formattedCurrencyString)

module.exports = exports

