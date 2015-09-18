# ---- begin references
credentialsStore = require './10_credentials_store'
# ---- end references

# api functions
api = {}

# ###################################################
# Internal Functions

buildFileHash = (file, callbackFn)->
    reader = new FileReader()
    reader.onloadend = ()->
        binaryFileContents = reader.result
        fileHash = CryptoJS.SHA256(CryptoJS.enc.Latin1.parse(binaryFileContents)).toString()
        callbackFn(fileHash)
        return

    reader.onerror = (evt)->
        console.error('error reading file')
        return

    reader.readAsBinaryString(file)
    return

signRequest = (xhr, xhrOptions)->
    credentials = credentialsStore.getCredentials()
    return if not credentials.apiToken?.length

    nonce = newNonce()
    if xhrOptions.data? and xhrOptions.data != 'null'
        if xhrOptions.data instanceof FormData and xhrOptions.paramsToSign?
            # console.log "xhrOptions.paramsToSign=",xhrOptions.paramsToSign
            paramsBody = window.JSON.stringify(xhrOptions.paramsToSign)
        else if typeof xhrOptions.data == 'object'
            paramsBody = window.JSON.stringify(xhrOptions.data)
            # console.log "paramsBody=",paramsBody
        else
            paramsBody = xhrOptions.data
    else
        paramsBody = '{}'

    
    # strip off any query
    parser = document.createElement('a')
    parser.href = window.location.protocol + '//' + window.location.host + xhrOptions.url
    url = parser.protocol + '//' + parser.host + parser.pathname

    signature = signURLParameters(xhrOptions.method, url, paramsBody, nonce, credentials)

    xhr.setRequestHeader('X-Tokenly-Auth-Nonce', nonce)
    xhr.setRequestHeader('X-Tokenly-Auth-Api-Token', credentials.apiToken)
    xhr.setRequestHeader('X-Tokenly-Auth-Signature', signature)

    return

signURLParameters = (method, url, paramsBody, nonce, credentials)->
    hmacMessage = method + "\n" + url + "\n" + paramsBody + "\n" + credentials.apiToken + "\n" + nonce
    # console.log "hmacMessage=", hmacMessage
    signature = CryptoJS.HmacSHA256(hmacMessage, credentials.apiSecretKey).toString(CryptoJS.enc.Base64)
    return signature

newNonce = ()->
    return Math.round( 0 + new Date() / 1000)


# ###################################################
# Api

api.getSelf = ()->
    # this is to see if we are logged in successfully
    return api.send('GET', 'users/me')

api.newBot = (botAttributes)->
    return api.send('POST', 'bots', botAttributes)

api.updateBot = (id, botAttributes)->
    return api.send('PUT', "bots/#{id}", botAttributes)

api.shutdownBot = (id, botAttributes)->
    return api.send('POST', "bots/shutdown/#{id}", botAttributes)


api.getAllBots = ()->
    return api.send('GET', 'bots')

api.getBot = (id)->
    return api.send('GET', "bots/#{id}")

api.getBotEvents = (id, additionalOpts={})->
    return api.send('GET', "botevents/#{id}", null, additionalOpts)

# 1 event per swap
api.getSwapEventStream = (id, additionalOpts={})->
    return api.send('GET', "swapevents/#{id}", null, additionalOpts)

api.getSwap = (id)->
    return api.send('GET', "swaps/#{id}")


api.refreshBalances = (id)->
    # always does this in the background
    return api.send('POST', "balancerefresh/#{id}", null, {background: true})


api.newUser = (userAttributes)->
    return api.send('POST', 'users', userAttributes)

api.updateUser = (id, userAttributes)->
    return api.send('PUT', "users/#{id}", userAttributes)

api.getAllUsers = ()->
    return api.send('GET', 'users')

api.getUser = (id)->
    return api.send('GET', "users/#{id}")


api.newSettings = (settingAttributes)->
    return api.send('POST', 'settings', settingAttributes)

api.updateSettings = (id, settingAttributes)->
    return api.send('PUT', "settings/#{id}", settingAttributes)

api.getAllSettings = ()->
    return api.send('GET', 'settings')

api.getSettings = (id)->
    return api.send('GET', "settings/#{id}")


api.getBotPaymentBalances = (id)->
    return api.send('GET', "payments/#{id}/balances")

api.getAllBotPayments = (id)->
    return api.send('GET', "payments/#{id}/all")


api.getAllPlansData = ()->
    return api.send('GET', "plans")


api.uploadImage = (files)->
    deferred = m.deferred()

    formData = new FormData
    rawFormData = []
    if files.length > 1
        console.error('only 1 image may be uploaded')
        return

    additionalOpts = {
        serialize: (value) ->
            value
    }

    formData.append 'image', files[0]
    buildFileHash files[0], (fileHash)->
        formData.append 'filehash', fileHash
        additionalOpts.paramsToSign = {filehash: fileHash}
        return api.send('POST', "images", formData, additionalOpts).then(
            (apiResponse)-> 
                deferred.resolve(apiResponse)
                return
            , (errorResponse)-> 
                deferred.reject(errorResponse)
                return
        )

        return

    return deferred.promise

api.getBotsForAllUsers = ()->
    return api.send('GET', 'bots', {allusers: ''})

api.getSwapsForAllUsers = (filters=null)->
    return api.send('GET', 'swaps', filters)



# ############################################################

api.send = (method, apiPathSuffix, params=null, additionalOpts={})->

    path = '/api/v1/'+apiPathSuffix

    # console.log "FAKE request"
    # dfd = m.deferred()
    # setTimeout ()->
    #     dfd.resolve({'fake': 'data'})
    # , 2500
    # return dfd.promise

    opts = {
        method: method,
        url: path,
        data: params,
        config: signRequest,
        # background: true,
    }

    opts.extract = (xhr, xhrOptions)->
        try
            # make sure the json is valid
            if xhr.responseText.length
                json = window.JSON.parse(xhr.responseText)

            # check code
            code = ""+xhr.status
            if code.substr(0, 1) != '2'
                if json?.errors? and json.errors.length > 0
                    newError = new Error()
                    newError.errors = json.errors
                    newError.message = json.message
                    throw newError
                throw new Error('invalid response code: '+code)

            # all is good
            if xhr.responseText.length
                return xhr.responseText

            return '""'

        catch e
            # pass through an error with the errors property already set
            if e.errors?
                throw e

            console.error "e=",e
            code = xhr.status
            errMsg = "Received an invalid response from server (#{code})"
            newError = new Error()
            newError.errors = [errMsg]
            throw newError


    # merge additionalOpts
    opts[k] = v for k, v of additionalOpts

    return m.request(opts)



module.exports = api
