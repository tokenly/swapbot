# currencyutils functions
sbAdmin.currencyutils = do ()->
    currencyutils = {}

    SATOSHI = 100000000

    currencyutils.satoshisToValue = (amount, currencyPostfix='BTC') ->
        return currencyutils.formatValue(amount / SATOSHI, currencyPostfix)

    currencyutils.formatValue = (value, currencyPostfix='BTC') ->
        if not value? or isNaN(value) or value.length == 0 then return ''
        return window.numeral(value).format('0,0.[00000000]') + (if currencyPostfix.length then ' '+currencyPostfix else '')

    currencyutils.formatFiatCurrency = (value, currencyPrefix='$')->
        if not value? or isNaN(value) or value.length == 0 then return ''
        formattedCurrencyString = window.numeral(value).format('0,0.00')
        prefix = ''
        if formattedCurrencyString == '0.00'
            prefix = 'less than '
            formattedCurrencyString = '0.01'
        return prefix+(if currencyPrefix?.length then currencyPrefix else '')+(formattedCurrencyString)


    return currencyutils
