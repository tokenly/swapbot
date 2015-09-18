# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
# ---- end references

# auth functions
auth = {}

auth.redirectIfNotLoggedIn = ()->
    if not auth.isLoggedIn()
        # m.route('/admin/login')
        window.location.href = '/account/login'
    return

auth.isLoggedIn = ()->
    credentials = auth.getCredentials()
    if credentials.apiToken?.length > 0 and credentials.apiSecretKey?.length > 0
        return true
    return false

auth.getUser = ()->
    user = window.JSON.parse(localStorage.getItem("user"))
    if not user then return {}
    return user

auth.hasPermssion = (requiredPermission)->
    user = auth.getUser()
    if not user.privileges? then return false

    if user.privileges[requiredPermission]
        return true

    return false

# returns a promise
auth.login = (apiToken, apiSecretKey)->
    window.localStorage.setItem("apiToken", apiToken)
    window.localStorage.setItem("apiSecretKey", apiSecretKey)

    return sbAdmin.api.getSelf().then (user)->
        # console.log "logged in user: ", user
        window.localStorage.setItem("user", window.JSON.stringify(user))
        return user


auth.logout = ()->
    window.localStorage.removeItem("apiToken")
    window.localStorage.removeItem("apiSecretKey")
    window.localStorage.removeItem("user")
    return

auth.getCredentials = ()->
    return {
        apiToken: localStorage.getItem("apiToken")
        apiSecretKey: localStorage.getItem("apiSecretKey")
    }

module.exports = auth
