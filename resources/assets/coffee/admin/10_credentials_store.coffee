# credentialsStore functions
credentialsStore = {}

credentialsStore.save = (apiToken, apiSecretKey)->
    window.localStorage.setItem("apiToken", apiToken)
    window.localStorage.setItem("apiSecretKey", apiSecretKey)
    return

credentialsStore.clear = ()->
    window.localStorage.removeItem("apiToken")
    window.localStorage.removeItem("apiSecretKey")
    return

credentialsStore.getCredentials = ()->
    return {
        apiToken: localStorage.getItem("apiToken")
        apiSecretKey: localStorage.getItem("apiSecretKey")
    }

module.exports = credentialsStore
