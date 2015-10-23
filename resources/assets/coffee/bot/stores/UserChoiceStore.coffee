# ---- begin references
BotConstants = require '../constants/BotConstants'
Dispatcher = require '../dispatcher/Dispatcher'
QuotebotStore = require '../stores/QuotebotStore'
SwapsStore = require '../stores/SwapsStore'
SwapMatcher = require '../util/SwapMatcher'
swapbot = swapbot or {}; swapbot.swapUtils = require '../../shared/swapUtils'
# ---- end references

exports = {}

MATCH_AUTO     = BotConstants.USERCHOICE_MATCH_AUTO
MATCH_SHOW_ALL = BotConstants.USERCHOICE_MATCH_SHOW_ALL

userChoices = {
    step                : 'choose'
    swapConfig          : {}
    inAmount            : null
    inAsset             : null
    outAmount           : null
    outAsset            : null
    lockedInRate        : null
    swap                : null
    swapMatchMode       : MATCH_AUTO
    swapIDsToIgnore     : {}
    numberOfIgnoredSwaps: 0
    numberOfMatchedSwaps: 0
    numberOfValidSwaps  : 0

    email:
        value     : ''
        level     : 0
        submitting: false
        submitted : false
        errorMsg  : null

}

router = null
eventEmitter = null

resetUserChoices = ()->
    # does not reset the step
    userChoices.swapConfig           = null
    userChoices.inAmount             = null
    userChoices.inAsset              = null
    userChoices.outAmount            = null
    userChoices.outAsset             = null
    userChoices.direction            = null
    userChoices.lockedInRate         = null
    userChoices.swap                 = null
    userChoices.swapMatchMode        = MATCH_AUTO
    userChoices.swapIDsToIgnore      = {}
    userChoices.numberOfIgnoredSwaps = 0
    userChoices.numberOfMatchedSwaps = 0
    userChoices.numberOfValidSwaps   = 0

    userChoices.z = false
    resetEmailChoices()
    return

resetEmailChoices = ()->
    userChoices.email = {
        value     : ''
        level     : 0
        submitting: false
        submitted : false
        errorMsg  : null
    }
    return


updateChosenAssetAndDirection = (asset, isSell)->
    direction = (if isSell then BotConstants.DIRECTION_SELL else BotConstants.DIRECTION_BUY)
    userChoices.direction = direction

    # set the new swapConfig
    if direction == BotConstants.DIRECTION_SELL
        userChoices.outAsset = asset
        userChoices.inAsset = null
    else
        userChoices.outAsset = null
        userChoices.inAsset = asset



    # move on to step place
    routeToStepOrEmitChange('place')

    return

updateChosenSwapConfig = (newChosenSwapConfig, currentRate)->
    newName = newChosenSwapConfig.in+':'+newChosenSwapConfig.out
    if not userChoices.swapConfig? or userChoices.swapConfig.name != newName

        # set the new swapConfig
        userChoices.swapConfig = newChosenSwapConfig
        userChoices.swapConfig.name = newName
        userChoices.lockedInRate = currentRate

        # calculate the new inAmount based on the outAmount
        _recalculateSwapConfigArtifacts()

        # move on to step receive
        matched = checkForAutoMatch()
        return if matched

        router.setRoute('confirmwallet')

    return

updateSwapAmount = (newAmount, amountType)->
    if amountType == 'in'
        if newAmount == userChoices.inAmount then return
    else
        if newAmount == userChoices.outAmount then return


    if amountType == 'in'
        # set the new inAmount
        userChoices.inAmount = newAmount
    else
        # set the new outAmount
        userChoices.outAmount = newAmount

    # calculate the new inAmount based on the outAmount
    _recalculateSwapConfigArtifacts()

    emitChange()

    return


updateChosenSwap = (newChosenSwap)->
    if not userChoices.swap? or userChoices.swap.id != newChosenSwap.id

        # set the new swap
        userChoices.swap = newChosenSwap

        # check for a completed swap
        if swapIsComplete(newChosenSwap)
            routeToStepOrEmitChange('complete')
            return

        # emit a change
        # console.log "updateChosenSwap complete"
        routeToStepOrEmitChange('wait')

    return


confirmWallet = ()->
    routeToStepOrEmitChange('receive')
    return

clearChosenSwap = ()->
    if userChoices.swap?
        userChoices.swap = null

        # clear email
        resetEmailChoices()
    return

clearChosenSwapConfig = ()->
    clearChosenSwap()
    userChoices.swapConfig = null;
    emitChange()
    return

resetSwap = ()->
    resetUserChoices()
    routeToStepOrEmitChange('choose')
    return

updateEmailValue = (email)->
    if email != userChoices.email.value
        userChoices.email.value = email
        emitChange()
    return

updateEmailLevel = (level)->
    if level != userChoices.email.level
        userChoices.email.level = level
        # console.log "userChoices.email.level=",userChoices.email.level
        emitChange()
    return

submitEmail = ()->
    return if userChoices.email.submittingEmail

    userChoices.email.submittingEmail = true
    userChoices.email.emailErrorMsg = null

    data = {email: userChoices.email.value, level: userChoices.email.level, swapId: userChoices.swap.id}
    $.ajax({
        type: "POST",
        url: '/api/v1/public/customers',
        data: data,
        dataType: 'json'

        success: (data)->
            if data.id
                userChoices.email.submittedEmail = true
                userChoices.email.submittingEmail = false

                # success. states were updated
                emitChange()

            return

        error: (jqhr, textStatus)->
            data = if jqhr.responseText then $.parseJSON(jqhr.responseText) else null
            if data?.message
                errorMsg = data.message
            else
                errorMsg = "An error occurred while trying to submit this email."

            console.error("Error: #{textStatus}", data)

            userChoices.email.submittedEmail = false
            userChoices.email.submittingEmail = false
            userChoices.email.emailErrorMsg = errorMsg
    
            # failure. states were updated
            emitChange()

            return
    })

    # marked as submitting
    emitChange()

    return



goBack = ()->
    switch userChoices.step
        when 'place'
            resetUserChoices()
            router.setRoute('/choose')
        when 'confirmwallet'
            router.setRoute('/place')
        when 'receive'
            userChoices.swapConfig           = null
            userChoices.inAmount             = null
            userChoices.inAsset              = null
            userChoices.swapMatchMode        = MATCH_AUTO
            userChoices.swapIDsToIgnore      = {}
            userChoices.numberOfIgnoredSwaps = 0
            userChoices.numberOfMatchedSwaps = 0
            userChoices.numberOfValidSwaps   = 0

            router.setRoute('/place')
        when 'wait'
            clearChosenSwap()
            router.setRoute('/receive')
    return

ignoreAllSwaps = ()->
    # ignore all the swaps that we've matched so far
    for swap in SwapsStore.getSwaps()
        if not userChoices.swapIDsToIgnore[swap.id]?
            userChoices.swapIDsToIgnore[swap.id] = true
            ++userChoices.numberOfIgnoredSwaps

    return


# #############################################

changeSwapMatchMode = (newSwapMatchMode)->
    userChoices.swapMatchMode = newSwapMatchMode
    checkForAutoMatch()
    return


checkForAutoMatch = ()->
    matchedSwaps = refreshMatchedSwaps()

    # all auto matching is disabled for now
    #   https://github.com/tokenly/swapbot/issues/199
    return false;

    # if userChoices.swapMatchMode != MATCH_AUTO then return false

    # if userChoices.numberOfMatchedSwaps == 1
    #     matchedSingleSwap = matchedSwaps[0]
    #     updateChosenSwap(matchedSingleSwap)
    #     return true

    # return false

refreshMatchedSwaps = ()->
    userChoices.numberOfMatchedSwaps = 0
    userChoices.numberOfValidSwaps = 0

    if not userChoices.inAsset? or not userChoices.inAmount
        return null

    matchedSwaps = SwapMatcher.buildMatchedSwaps(SwapsStore.getSwaps(), userChoices)
    userChoices.numberOfMatchedSwaps = matchedSwaps.length

    validSwaps = SwapMatcher.buildValidSwaps(SwapsStore.getSwaps(), userChoices)
    userChoices.numberOfValidSwaps = validSwaps.length

    return matchedSwaps


swapIsComplete = (newChosenSwap)->
    return true if newChosenSwap.isComplete
    return false

# 
# #############################################

routeToStep = (newStep)->
    if userChoices.step != newStep
        router.setRoute('/'+newStep)
        return true

    return false

routeToStepOrEmitChange = (newStep)->
    if routeToStep(newStep)
        return

    emitChange()
    return


emitChange = ()->
    eventEmitter.emitEvent('change')
    return

_recalculateSwapConfigArtifacts = ()->
    if userChoices.direction == BotConstants.DIRECTION_SELL
        # calculate the new inAmount based on the outAmount
        if userChoices.outAmount? and userChoices.swapConfig?
            userChoices.inAmount = swapbot.swapUtils.inAmountFromOutAmount(userChoices.outAmount, userChoices.swapConfig, userChoices.lockedInRate)
    else
        # calculate the new outAmount based on the inAmount
        if userChoices.inAmount? and userChoices.swapConfig?
            userChoices.outAmount = swapbot.swapUtils.outAmountFromInAmount(userChoices.inAmount, userChoices.swapConfig)
    # console.log "_recalculateSwapConfigArtifacts userChoices.direction=#{userChoices.direction} userChoices.inAmount=#{userChoices.inAmount} userChoices.outAmount=#{userChoices.outAmount}"

    if userChoices.swapConfig
        userChoices.outAsset = userChoices.swapConfig.out
        userChoices.inAsset = userChoices.swapConfig.in

    return

# #############################################

onRouteUpdate = (rawNewStep)->
    newStep = rawNewStep
    valid = true

    switch rawNewStep
        when 'choose'
            # all good
            valid = true
            
        when 'place', 'confirmwallet', 'receive', 'wait', 'complete'
            if userChoices.direction == null
                # no out amount was chosen - go back
                valid = false
        else
            # unknown stage
            console.warn "Unknown route: #{rawNewStep}"
            valid = false


    if not valid
        resetUserChoices()
        if rawNewStep != 'choose'
            router.setRoute('/choose')
        return false

    # if no change was made, don't continue
    if newStep == userChoices.step
        return false

    # save the new step
    userChoices.step = newStep

    # handle updates on routing
    switch newStep
        when 'choose'
            # start over
            resetUserChoices()
        when 'place'
            # clear the chose swapConfig
            clearChosenSwapConfig()

    # emit change
    emitChange()

    return true

initRouter = ()->
    router = Router({
        '/choose'       : onRouteUpdate.bind(null, 'choose'),
        '/place'        : onRouteUpdate.bind(null, 'place'),
        '/confirmwallet': onRouteUpdate.bind(null, 'confirmwallet'),
        '/receive'      : onRouteUpdate.bind(null, 'receive'),
        '/wait'         : onRouteUpdate.bind(null, 'wait'),
        '/complete'     : onRouteUpdate.bind(null, 'complete'),
    })

    router.init(userChoices.step)
    return



# #############################################

onSwapStoreChanged = ()->
    if userChoices.swap?.id
        # a swap has been chosen
        #   update the user choice if the swap was updated
        swap = SwapsStore.getSwapById(userChoices.swap.id)

        # update the chosen swap when the swapStore changes it
        userChoices.swap = swap

        # check for a completed swap
        if swapIsComplete(swap)
            routeToStepOrEmitChange('complete')
            return

        emitChange()
    else
        # swap not chosen yet
        #   check for an automatched swap
        matched = checkForAutoMatch()
        return if matched

    return

# #############################################

onQuotebotPriceUpdated = ()->
    price = QuotebotStore.getCurrentPrice()
    return

# #############################################

handleSwapstreamEvents = (eventWrappers)->
    anyChanged = false
    for eventWrapper in eventWrappers
        swapId = eventWrapper.swapUuid
        event = eventWrapper.event

        # check for a swap.replaced event
        if event.name == 'swap.replaced'
            oldSwapId = eventWrapper.swapUuid
            replacingSwapId = event.newUuid
            # console.log "replacing swap #{oldSwapId} with swap #{replacingSwapId}"

            replacingSwap = SwapsStore.getSwapById(replacingSwapId)
            if not replacingSwap?
                console.error("could not find new swap by swap id #{replacingSwapId}")

            if not userChoices.swap? or userChoices.swap.id != replacingSwap.id
                # console.log "new swap id is #{replacingSwapId}"
                userChoices.swap = replacingSwap
                emitChange()
    
    return


# #############################################

exports.init = ()->
    # init emitter
    eventEmitter = new window.EventEmitter()

    # register with the app dispatcher
    Dispatcher.register (action)->
        switch action.actionType

            when BotConstants.BOT_USER_CHOOSE_ASSET
                updateChosenAssetAndDirection(action.asset, action.isSell)

            when BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG
                updateChosenSwapConfig(action.swapConfig, action.currentRate)

            when BotConstants.BOT_USER_CHOOSE_SWAP
                updateChosenSwap(action.swap)

            when BotConstants.BOT_USER_CONFIRM_WALLET
                confirmWallet()

            when BotConstants.BOT_USER_CLEAR_SWAP
                clearChosenSwap()

                # disable automatching
                changeSwapMatchMode(MATCH_SHOW_ALL)

                # go back to the wait step if we aren't there
                routeToStepOrEmitChange('receive')


            when BotConstants.BOT_USER_RESET_SWAP
                resetSwap()

            when BotConstants.BOT_USER_ENTERED_SWAP_AMOUNT
                updateSwapAmount(action.amount, action.amountType)


            when BotConstants.BOT_UPDATE_EMAIL_VALUE
                updateEmailValue(action.email)

            when BotConstants.BOT_UPDATE_EMAIL_LEVEL_VALUE
                updateEmailLevel(action.level)

            when BotConstants.BOT_USER_SUBMIT_EMAIL
                submitEmail()


            when BotConstants.BOT_GO_BACK
                goBack()

            when BotConstants.BOT_IGNORE_ALL_PREVIOUS_SWAPS
                ignoreAllSwaps()

                # enable automatching
                changeSwapMatchMode(MATCH_AUTO)

                # go back to the wait step if we aren't there
                routeToStepOrEmitChange('receive')

            when BotConstants.BOT_SHOW_ALL_TRANSACTIONS
                clearChosenSwap()

                # disable automatching
                changeSwapMatchMode(MATCH_SHOW_ALL)

                # go back to the wait step if we aren't there
                routeToStepOrEmitChange('receive')

            when BotConstants.UI_BEGIN_SWAPS
                routeToStep('choose')

            when BotConstants.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS
                handleSwapstreamEvents(action.swapstreamEvents)

            # else
            #     console.log "unknown action: #{action.actionType}"
        return

    # set default choices
    resetUserChoices()

    # init router
    initRouter()

    SwapsStore.addChangeListener ()->
        onSwapStoreChanged()
        return

    return

exports.getUserChoices = ()->
    return $.extend({}, userChoices)

exports.addChangeListener = (callback)->
    eventEmitter.addListener('change', callback)
    return

exports.removeChangeListener = (callback)->
    eventEmitter.removeListener('change', callback)
    return

# #############################################
module.exports = exports



