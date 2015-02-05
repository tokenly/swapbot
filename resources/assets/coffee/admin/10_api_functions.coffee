# api functions
sbAdmin.api = do ()->
    api = {}

    # ###################################################
    # Internal Functions

    errorHandler = (error)->
        console.log "an error occurred", error
        return

    signRequest = (xhr, xhrOptions)->
        # console.log "xhr=", xhr
        # console.log "xhrOptions=", xhrOptions
        credentials = sbAdmin.auth.getCredentials()
        return if not credentials.apiToken?.length

        nonce = newNonce()
        if xhrOptions.data?
            if typeof xhrOptions.data == 'object'
                paramsBody = window.JSON.stringify(xhrOptions.data)
            else
                paramsBody = xhrOptions.data
        else
            paramsBody = '{}'
        # console.log "paramsBody=#{paramsBody}", paramsBody

        url = window.location.protocol + '//' + window.location.host + xhrOptions.url

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

    api.getAllBots = ()->
        return api.send('GET', 'bots')

    api.getBot = (id)->
        return api.send('GET', "bots/#{id}")

    api.getBotEvents = (id, additionalOpts={})->
        return api.send('GET', "botevents/#{id}", null, additionalOpts)


    api.newUser = (userAttributes)->
        return api.send('POST', 'users', userAttributes)

    api.updateUser = (id, userAttributes)->
        return api.send('PUT', "users/#{id}", userAttributes)

    api.getAllUsers = ()->
        return api.send('GET', 'users')

    api.getUser = (id)->
        return api.send('GET', "users/#{id}")



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

        # merge additionalOpts
        opts[k] = v for k, v of additionalOpts

        return m.request(opts)

    return api
