do ()->

    sbAdmin.ctrl.botForm = {}

    constants = sbAdmin.constants


    # ################################################

    buildIncomeRulesGroup = ()->
        return sbAdmin.formGroup.newGroup({
            id: 'incomerules'
            fields: [
                {name: 'asset', }
                {name: 'minThreshold', }
                {name: 'paymentAmount', }
                {name: 'address', }
            ]
            addLabel: "Add Another Income Forwarding Rule"
            buildItemRow: (builder, number, item)->
                return [
                    builder.header("Income Forwarding Rule ##{number}"),
                    builder.row([
                        builder.field("Asset Received", 'asset', 'BTC', 3), 
                        builder.field("Trigger Threshold", 'minThreshold', {type: "number", step: "any", min: "0", placeholder: "1.0"}), 
                        builder.field("Payment Amount", 'paymentAmount', {type: "number", step: "any", min: "0", placeholder: "0.5"}), 
                        builder.field("Payment Address", 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4), 
                    ]),
                ]
        })

    # ################################################

    buildBlacklistAddressesGroup = ()->
        return sbAdmin.formGroup.newGroup({
            id: 'blacklist'
            fields: [
                {name: 'address', }
            ]
            addLabel: " Add Another Blacklist Address"
            buildItemRow: (builder, number, item)->
                return [
                    builder.row([
                        builder.field(null, 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4), 
                    ]),
                ]
            translateFieldToNumberedValues: 'address'
            useCompactNumberedLayout: true
        })

    # ################################################

    buildOffsetKey = (swapDirection, offset)->
        return swapDirection+"_"+offset


    buildDuplicateSwapOffsetsMap = (buySwaps, sellSwaps)->
        duplicateOffsetsMap = {}
        offsetByToken = {}

        buildMapFn = (swapDirection)->
            return (swap, offset)->
                offsetKey = buildOffsetKey(swapDirection, offset)
                inToken = swap().in().toUpperCase()
                if not inToken then return

                if offsetByToken[inToken]?
                    duplicateOffsetsMap[offsetByToken[inToken]] = true
                    duplicateOffsetsMap[offsetKey] = true
                else
                    offsetByToken[inToken] = offsetKey
                return

        buySwaps().map(buildMapFn(constants.DIRECTION_BUY))
        sellSwaps().map(buildMapFn(constants.DIRECTION_SELL))

        return duplicateOffsetsMap

    splitBotDataIntoBuyAndSellSwaps = (botDataSwaps)->
        buySwapsData = []
        sellSwapsData = []

        botDataSwaps.map (swapData)->
            if swapData.direction == constants.DIRECTION_BUY
                buySwapsData.push(swapData)
            else
                sellSwapsData.push(swapData)
            return

        return [buySwapsData, sellSwapsData]


    mergeSwaps = (buySwaps, sellSwaps)->
        mergedSwaps = []

        mapFn = (swapProp, offset)->
            swapArray = swapProp()
            if swapArray.in()?.length
                mergedSwaps.push(swapArray)
            return

        buySwaps.map(mapFn)
        sellSwaps.map(mapFn)


        return mergedSwaps

    # ################################################

    vm = sbAdmin.ctrl.botForm.vm = do ()->

        buildBlacklistAddressesPropValue = (addresses)->
            out = []
            for address in addresses
                out.push(m.prop(address))

            # always have at least one
            if not out.length
                out.push(m.prop(''))

            return out

        vm = {}
        vm.init = ()->
            # view status
            vm.errorMessages = m.prop([])
            vm.formStatus = m.prop('active')
            vm.resourceId = m.prop('')
            vm.allPlansData = m.prop(null)

            # fields
            vm.name = m.prop('')
            vm.description = m.prop('')
            vm.hash = m.prop('')
            vm.paymentPlan = m.prop('monthly001')
            vm.returnFee = m.prop(0.0001)
            vm.confirmationsRequired = m.prop(2)
            vm.refundAfterBlocks = m.prop(3)
            vm.sellSwaps = m.prop([sbAdmin.swaputils.newSwapProp({direction: constants.DIRECTION_SELL})])
            vm.buySwaps = m.prop([])
            
            vm.incomeRulesGroup = buildIncomeRulesGroup()
            vm.blacklistAddressesGroup = buildBlacklistAddressesGroup()


            vm.backgroundOverlaySettings = m.prop(window.JSON.stringify(sbAdmin.botutils.defaultOverlay()))
            vm.backgroundImageDetails = m.prop('')
            vm.backgroundImageId      = m.prop('')
            vm.logoImageDetails       = m.prop('')
            vm.logoImageId            = m.prop('')
            # if there is an id, then load it from the api
            id = m.route.param('id')
            vm.isNew = (id == 'new')
            if !vm.isNew
                # load the bot info from the api
                sbAdmin.api.getBot(id).then(
                    (botData)->
                        vm.resourceId(botData.id)

                        vm.name(botData.name)
                        vm.description(botData.description)
                        vm.hash(botData.hash)
                        vm.paymentPlan(botData.paymentPlan)
                        vm.returnFee(botData.returnFee or "0.0001")
                        vm.confirmationsRequired(botData.confirmationsRequired or "2")
                        vm.refundAfterBlocks(botData.refundConfig?.refundAfterBlocks or "3")

                        # split swaps into buy and sell
                        [buySwapsData, sellSwapsData] = splitBotDataIntoBuyAndSellSwaps(botData.swaps)
                        vm.sellSwaps(sbAdmin.swaputils.buildSwapsPropValue(sellSwapsData))
                        vm.buySwaps(sbAdmin.swaputils.buildSwapsPropValue(buySwapsData))

                        vm.incomeRulesGroup.unserialize(botData.incomeRules)
                        vm.blacklistAddressesGroup.unserialize(botData.blacklistAddresses)

                        vm.backgroundOverlaySettings(if botData.backgroundOverlaySettings?.start then window.JSON.stringify(botData.backgroundOverlaySettings) else '')
                        vm.backgroundImageDetails(botData.backgroundImageDetails)
                        vm.backgroundImageId(botData.backgroundImageDetails?.id)

                        vm.logoImageDetails(botData.logoImageDetails)
                        vm.logoImageId(botData.logoImageDetails?.id)

                        return
                    , (errorResponse)->
                        vm.errorMessages(errorResponse.errors)
                        return
                )

            # get the plan options
            sbAdmin.api.getAllPlansData().then(
                (apiResponse)->
                    vm.allPlansData(apiResponse)
                    return
                , (errorResponse)->
                    vm.errorMessages(errorResponse.errors)
                    return
            )



            vm.save = (e)->
                e.preventDefault()

                attributes = {
                    name: vm.name()
                    description: vm.description()
                    hash: vm.hash()
                    paymentPlan: vm.paymentPlan()
                    # need to combine these...
                    swaps: sbAdmin.swaputils.normalizeSwapsForSaving(mergeSwaps(vm.sellSwaps(), vm.buySwaps()))
                    returnFee: vm.returnFee() + ""
                    incomeRules: vm.incomeRulesGroup.serialize()
                    blacklistAddresses: vm.blacklistAddressesGroup.serialize()
                    confirmationsRequired: vm.confirmationsRequired() + ""
                    refundConfig: {refundAfterBlocks: vm.refundAfterBlocks() + ""}
                    backgroundImageId: vm.backgroundImageId() or ''
                    backgroundOverlaySettings: if vm.backgroundOverlaySettings() then window.JSON.parse(vm.backgroundOverlaySettings()) else ''
                    logoImageId: vm.logoImageId() or ''
                }

                if vm.resourceId().length > 0
                    # update existing bot
                    apiCall = sbAdmin.api.updateBot
                    apiArgs = [vm.resourceId(), attributes]
                else
                    # new bot
                    apiCall = sbAdmin.api.newBot
                    apiArgs = [attributes]

                sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then((apiResponse)->
                    # console.log "submit complete - routing to dashboard"
                    # go to bot display

                    if vm.isNew
                        # console.log "apiResponse=",apiResponse
                        botId = apiResponse.id
                    else
                        botId = vm.resourceId()

                    m.route("/admin/view/bot/#{botId}")
                    return
                )

            return
        return vm

    sbAdmin.ctrl.botForm.controller = ()->
        # require login
        sbAdmin.auth.redirectIfNotLoggedIn()

        vm.init()
        return

    sbAdmin.ctrl.botForm.view = ()->
        duplicateSwapsOffsetsMap = buildDuplicateSwapOffsetsMap(vm.buySwaps, vm.sellSwaps)

        mEl = m("div", [
            m("div", { class: "row"}, [
                m("div", {class: "col-md-12"}, [

                    m("div", { class: "row"}, [
                        m("div", {class: "col-md-10"}, [
                            m("h2", if vm.resourceId() then "Edit SwapBot #{vm.name()}" else "Create a New Swapbot"),
                        ]),
                        m("div", {class: "col-md-2 text-right"}, [
                            sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead'),
                        ]),
                    ]),


                    m("div", {class: "spacer1"}),

                    # m("form", {onsubmit: vm.save, }, [
                    sbAdmin.form.mForm({errors: vm.errorMessages, status: vm.formStatus}, {onsubmit: vm.save}, [
                        sbAdmin.form.mAlerts(vm.errorMessages),

                        m("h3", "Look and Feel"),

                        sbAdmin.form.mFormField("Bot Name", {id: 'name', 'placeholder': "Bot Name", required: true, }, vm.name),
                        sbAdmin.form.mFormField("Bot Description", {type: 'textarea', id: 'description', 'placeholder': "Bot Description", required: true, }, vm.description),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-8"}, [
                                sbAdmin.fileHelper.mImageUploadAndDisplay("Custom Background Image", {id: 'BGImage', sizeDesc: '1440 x 720 Image Recommended'}, vm.backgroundImageId, vm.backgroundImageDetails, 'medium'),
                            ]),
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.fileHelper.mImageUploadAndDisplay("Custom Logo Image", {id: 'LogoImage', sizeDesc: '100 x 100 Image Recommended'}, vm.logoImageId, vm.logoImageDetails, 'thumb'),
                            ]),
                        ]),

                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-8"}, [
                                sbAdmin.form.mFormField("Background Overlay", {id: "background_overlay", type: 'select', options: sbAdmin.botutils.overlayOpts()}, vm.backgroundOverlaySettings)
                            ]),
                        ]),

                        # -------------------------------------------------------------------------------------------------------------------------------------------
                        m("div", {class: "spacer1"}),
                        m("hr"),

                        m("h3", "Sell Tokens"),
                        sbAdmin.swapgrouprenderer.buildSwapsSection(constants.DIRECTION_SELL, duplicateSwapsOffsetsMap, vm),

                        m("div", {class: "spacer1"}),
                        m("h3", "Purchase Tokens"),
                        sbAdmin.swapgrouprenderer.buildSwapsSection(constants.DIRECTION_BUY, duplicateSwapsOffsetsMap, vm),



                        # -------------------------------------------------------------------------------------------------------------------------------------------

                        m("div", {class: "spacer1"}),
                        m("hr"),

                        # overflow/income address
                        m("h3", "Income Forwarding"),
                        m("p", [m("small", "When the bot fills up to a certain amount, you may forward the funds to your own destination address.")]),
                        vm.incomeRulesGroup.buildInputs(),




                        # -------------------------------------------------------------------------------------------------------------------------------------------
                        m("div", {class: "spacer1"}),
                        m("hr"),

                        m("h3", "Other Settings"),

                        # return fee
                        m("div", {class: "spacer1"}),
                        m("div", { class: "row"}, [
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.form.mFormField("Confirmations", {id: 'confirmations_required', 'placeholder': "2", type: "number", step: "1", min: "2", max: "6", required: true, }, vm.confirmationsRequired),
                            ]),
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.form.mFormField("Return Transaction Fee", {id: 'return_fee', 'placeholder': "0.0001", type: "number", step: "0.00001", min: "0.00001", max: "0.001", required: true, postfix: 'BTC' }, vm.returnFee),
                            ]),
                            m("div", {class: "col-md-4"}, [
                                sbAdmin.form.mFormField("Refund Out of Stock Swaps After", {id: 'refund_after_blocks', 'placeholder': "3", type: "number", step: "1", min: "3", max: "72", required: true, postfix: 'blocks' }, vm.refundAfterBlocks),
                            ]),
                        ]),

                        m("h5", "Blacklisted Addresses"),
                        m("p", [m("small", "Tokens received from blacklisted addresses do not trigger swaps.  These addresses can be used to fill the SwapBot with additional inventory.")]),
                        vm.blacklistAddressesGroup.buildInputs(),


                        # -------------------------------------------------------------------------------------------------------------------------------------------


                        m("div", {class: "spacer3"}),

                        m("a[href='/admin/dashboard']", {class: "btn btn-default pull-right", config: m.route}, "Return without Saving"),
                        sbAdmin.form.mSubmitBtn("Save Bot"),
                        m("a[href='/admin/shutdown/bot/#{vm.resourceId()}']", {class: "btn btn-warning ", config: m.route, style: {'margin-left': '24px'}}, "Shutdown Bot"),
                        

                    ]),

                ]),
            ]),



        ])
        return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)]


