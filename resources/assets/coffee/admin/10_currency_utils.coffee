# currencyutils functions
sbAdmin.currencyutils = do ()->
    currencyutils = {}

    SATOSHI = 100000000

    currencyutils.satoshisToValue = (amount, currencyPostfix='BTC') ->
        return currencyutils.formatValue(amount / SATOSHI, currencyPostfix)

    currencyutils.formatValue = (value, currencyPostfix='BTC') ->
        return window.numeral(value).format('0.0[0000000]') + (if currencyPostfix.length then ' '+currencyPostfix else '')

    return currencyutils
