UserChoiceStore = do ()->
    exports = {}

    userChoices = {
        step      : 'choose'
        swapConfig: {}
        inAmount  : null
        inAsset   : null
        outAmount : null
        outAsset  : null
        swap      : null

        email:
            value: ''
            submitting: false
            submitted: false
            errorMsg: null

    }
    
    router = null
    eventEmitter = null

    resetUserChoices = ()->
        # does not reset the step
        userChoices.swapConfig = null
        userChoices.inAmount   = null
        userChoices.inAsset    = null
        userChoices.outAmount  = null
        userChoices.outAsset   = null
        userChoices.swap       = null
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

            # move on to step receive
            routeToStepOrEmitChange('receive')

        return

    updateChosenSwapConfig = (newChosenSwapConfig)->
        newName = newChosenSwapConfig.in+':'+newChosenSwapConfig.out
        if not userChoices.swapConfig? or userChoices.swapConfig.name != newName

            # set the new swapConfig
            userChoices.swapConfig = newChosenSwapConfig
            userChoices.swapConfig.name = newName

            # calculate the new inAmount based on the outAmount
            _recalulateUserChoices()

            # move on to step wait
            router.setRoute('wait')

        return

    updateOutAmount = (newOutAmount)->
        if newOutAmount == userChoices.outAmount
            return

        # set the new outAmount
        userChoices.outAmount = newOutAmount

        # calculate the new inAmount based on the outAmount
        _recalulateUserChoices()

        emitChange()

        return


    updateChosenSwap = (newChosenSwap)->
        if not userChoices.swap? or userChoices.swap.id != newChosenSwap.id

            # set the new swap
            userChoices.swap = newChosenSwap

            # emit a change
            console.log "updateChosenSwap complete"
            emitChange()

        return

    clearChosenSwap = ()->
        if userChoices.swap?
            userChoices.swap = null
            emitChange()

            # clear email
            resetEmailChoices()

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
            when 'receive'
                resetUserChoices()
                router.setRoute('/choose')
            when 'wait'
                userChoices.swapConfig = null
                userChoices.inAmount   = null
                userChoices.inAsset    = null
                router.setRoute('/receive')
        return



    # #############################################

    routeToStepOrEmitChange = (newStep)->
        if userChoices.step != newStep
            router.setRoute('/'+newStep)
            return

        emitChange()


    emitChange = ()->
        eventEmitter.emitEvent('change')
        return

    _recalulateUserChoices = ()->
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
                
            when 'receive', 'wait', 'complete'
                if userChoices.outAsset == null
                    # no out amount was chosen - go back
                    valid = false
            # when 'receive', 'wait', 'complete'
            #     if not userChoices.swapConfig?
            #         # no swap chosen - go back
            #         valid = false
            #     if userChoices.step == 'complete'
            #         if not this.state.swapDetails.txInfo?
            #             # no txInfo found - go back
            #             valid = false
            else
                # unknown stage
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

        # emit change
        emitChange()

        return true

    initRouter = ()->
        router = Router({
            '/choose'  : onRouteUpdate.bind(null, 'choose'),
            '/receive' : onRouteUpdate.bind(null, 'receive'),
            '/wait'    : onRouteUpdate.bind(null, 'wait'),
            '/complete': onRouteUpdate.bind(null, 'complete'),
        })

        router.init(userChoices.step)
        return



    # #############################################

    onSwapStoreChanged = ()->
        if userChoices.swap?.id
            # update the chosen swap when the swapStore changes it
            userChoices.swap = SwapsStore.getSwapById(userChoices.swap.id)

            emitChange()

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



