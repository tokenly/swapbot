UserChoiceStore = do ()->
    exports = {}

    userChoices = {
        step          : 'choose'
        swapConfig    : {}
        inAmount      : null
        inAsset       : null
        outAmount     : null
        outAsset      : null
        swap          : null
        allowAutoMatch: true

        email:
            value     : ''
            submitting: false
            submitted : false
            errorMsg  : null

    }
    
    router = null
    eventEmitter = null

    resetUserChoices = ()->
        # does not reset the step
        userChoices.swapConfig     = null
        userChoices.inAmount       = null
        userChoices.inAsset        = null
        userChoices.outAmount      = null
        userChoices.outAsset       = null
        userChoices.swap           = null
        userChoices.allowAutoMatch = true
        resetEmailChoices()
        return

    resetEmailChoices = ()->
        userChoices.email = {
            value     : ''
            submitting: false
            submitted : false
            errorMsg  : null
        }
        return


    updateChosenOutAsset = (newChosenOutAsset)->
        if userChoices.outAsset != newChosenOutAsset

            # set the new swapConfig
            userChoices.outAsset = newChosenOutAsset

            # move on to step place
            routeToStepOrEmitChange('place')

        return

    updateChosenSwapConfig = (newChosenSwapConfig)->
        newName = newChosenSwapConfig.in+':'+newChosenSwapConfig.out
        if not userChoices.swapConfig? or userChoices.swapConfig.name != newName

            # set the new swapConfig
            userChoices.swapConfig = newChosenSwapConfig
            userChoices.swapConfig.name = newName

            # calculate the new inAmount based on the outAmount
            _recalculateSwapConfigArtifacts()

            # move on to step receive
            matched = checkForAutoMatch()
            return if matched

            router.setRoute('receive')

        return

    updateOutAmount = (newOutAmount)->
        if newOutAmount == userChoices.outAmount
            return

        # set the new outAmount
        userChoices.outAmount = newOutAmount

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

    submitEmail = ()->
        return if userChoices.email.submittingEmail

        userChoices.email.submittingEmail = true
        userChoices.email.emailErrorMsg = null

        data = {email: userChoices.email.value, swapId: userChoices.swap.id}
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
            when 'receive'
                userChoices.swapConfig     = null
                userChoices.inAmount       = null
                userChoices.inAsset        = null
                userChoices.allowAutoMatch = true

                router.setRoute('/place')
            when 'wait'
                clearChosenSwap()
                router.setRoute('/receive')
        return



    # #############################################

    checkForAutoMatch = ()->
        # console.log "userChoices.allowAutoMatch=#{userChoices.allowAutoMatch}"
        if not userChoices.allowAutoMatch then return false

        matchedSingleSwap = autoMatchTransaction()
        if matchedSingleSwap
            # console.log "Auto match found"
            updateChosenSwap(matchedSingleSwap)
            return true

        return false

    autoMatchTransaction = ()->
        if not userChoices.inAsset? or not userChoices.inAmount
            return null

        matchedSwaps = SwapMatcher.buildMatchedSwaps(SwapsStore.getSwaps(), userChoices)
        if matchedSwaps.length == 1
            return matchedSwaps[0]

        return null
    
    swapIsComplete = (newChosenSwap)->
        return true if newChosenSwap.isComplete
        return false

    # 
    # #############################################

    routeToStepOrEmitChange = (newStep)->
        if userChoices.step != newStep
            router.setRoute('/'+newStep)
            return

        emitChange()
        return


    emitChange = ()->
        eventEmitter.emitEvent('change')
        return

    _recalculateSwapConfigArtifacts = ()->
        # calculate the new inAmount based on the outAmount
        if userChoices.outAmount? and userChoices.swapConfig?
            userChoices.inAmount = swapbot.swapUtils.inAmountFromOutAmount(userChoices.outAmount, userChoices.swapConfig)

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
                
            when 'place', 'receive', 'wait', 'complete'
                if userChoices.outAsset == null
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
            '/choose'  : onRouteUpdate.bind(null, 'choose'),
            '/place'   : onRouteUpdate.bind(null, 'place'),
            '/receive' : onRouteUpdate.bind(null, 'receive'),
            '/wait'    : onRouteUpdate.bind(null, 'wait'),
            '/complete': onRouteUpdate.bind(null, 'complete'),
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

    exports.init = ()->
        # init emitter
        eventEmitter = new window.EventEmitter()

        # register with the app dispatcher
        Dispatcher.register (action)->
            switch action.actionType

                when BotConstants.BOT_USER_CHOOSE_OUT_ASSET
                    updateChosenOutAsset(action.outAsset)

                when BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG
                    updateChosenSwapConfig(action.swapConfig)

                when BotConstants.BOT_USER_CHOOSE_SWAP
                    updateChosenSwap(action.swap)

                when BotConstants.BOT_USER_CLEAR_SWAP
                    clearChosenSwap()

                    # disable automatching
                    userChoices.allowAutoMatch = false

                    # go back to the wait step if we aren't there
                    routeToStepOrEmitChange('receive')


                when BotConstants.BOT_USER_RESET_SWAP
                    resetSwap()

                when BotConstants.BOT_USER_CHOOSE_OUT_AMOUNT
                    updateOutAmount(action.outAmount)


                when BotConstants.BOT_UPDATE_EMAIL_VALUE
                    updateEmailValue(action.email)

                when BotConstants.BOT_USER_SUBMIT_EMAIL
                    submitEmail()


                when BotConstants.BOT_GO_BACK
                    goBack()

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
        return userChoices

    exports.addChangeListener = (callback)->
        eventEmitter.addListener('change', callback)
        return

    exports.removeChangeListener = (callback)->
        eventEmitter.removeListener('change', callback)
        return

    # #############################################
    return exports



