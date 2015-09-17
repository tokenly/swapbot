# ---- begin references
BotConstants = require '../../constants/BotConstants'
UserInputActions = require '../../actions/UserInputActions'
QuotebotStore = require '../../stores/QuotebotStore'
UserChoiceStore = require '../../stores/UserChoiceStore'
NeedHelpLink = require '../../views/includes/NeedHelpLink'
PlaceOrderInput = require '../../views/includes/PlaceOrderInput'
swapbot = swapbot or {}; swapbot.formatters = require '../../../shared/formatters'
swapbot = swapbot or {}; swapbot.quoteUtils = require '../../util/quoteUtils'
swapbot = swapbot or {}; swapbot.swapUtils = require '../../../shared/swapUtils'
# ---- end references

SwapbotPlaceOrder = null

getViewState = ()->
    return { 
        userChoices: UserChoiceStore.getUserChoices() 
        currentBTCPrice: QuotebotStore.getCurrentPrice() 
    }


# ############################################################################################################
# A Send Item

SwapbotSendItem = React.createClass
    displayName: 'SwapbotSendItem'

    getInAmount: ()->
        if this.props.direction == BotConstants.DIRECTION_SELL
            inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, this.props.swapConfig, this.props.currentBTCPrice)
            console.log "after inAmountFromOutAmount:  outAmount=#{this.props.outAmount} inAmount=#{inAmount}"
        else
            inAmount = this.props.inAmount

        return inAmount

    getOutAmount: ()->
        if this.props.direction == BotConstants.DIRECTION_SELL
            outAmount = this.props.outAmount
        else
            outAmount = swapbot.swapUtils.outAmountFromInAmount(this.props.inAmount, this.props.swapConfig)

        
        return outAmount

    isChooseable: (inAmount, outAmount)->
        if inAmount <= 0
            return false

        if outAmount > this.props.bot.balances[this.props.swapConfig.out]
            return false

        return true

    validateInAndOutAmounts: (inAmount, outAmount)->
        errors = swapbot.swapUtils.validateOutAmount(outAmount, this.props.swapConfig, this.props.bot.balances[this.props.swapConfig.out])
        return errors if errors?

        errors = swapbot.swapUtils.validateInAmount(inAmount, this.props.swapConfig)
        return errors if errors?

        return null



    buildChooseSwap: (inAmount, outAmount, isChooseable)->
        return (e)=>
            e.preventDefault()
            if not isChooseable then return

            UserInputActions.chooseSwapConfigAtRate(this.props.swapConfig, this.props.currentBTCPrice)

            return

    render: ()->
        swapConfig = this.props.swapConfig

        inAmount = this.getInAmount()
        outAmount = this.getOutAmount()
        # console.log "inAmount=#{inAmount} #{this.props.swapConfig.in} outAmount=#{outAmount} #{this.props.swapConfig.out}"

        errorMsg = this.validateInAndOutAmounts(inAmount, outAmount)
        if errorMsg
            isChooseable = false
        else 
            isChooseable = this.isChooseable(inAmount, outAmount)

        fiatSuffix = 
            <span className="fiatSuffix">
                { swapbot.quoteUtils.fiatQuoteSuffix(swapConfig, inAmount, swapConfig.in) }
            </span>
        changeMessage = swapbot.swapUtils.buildChangeMessage(outAmount, this.props.swapConfig, this.props.currentBTCPrice)

        if this.props.direction == BotConstants.DIRECTION_SELL
            transactionHeaderText = <span>Purchase {swapbot.formatters.formatCurrency(outAmount)} {swapConfig.out} for {swapbot.formatters.formatCurrency(inAmount)} {swapConfig.in}{fiatSuffix}</span>
        else
            transactionHeaderText = <span>Sell {swapbot.formatters.formatCurrency(inAmount)} {swapConfig.in} for {swapbot.formatters.formatCurrency(outAmount)} {swapConfig.out}</span>

        <li className={'choose-swap'+(if isChooseable then ' chooseable' else ' unchooseable')}>
            <a className="choose-swap" onClick={this.buildChooseSwap(inAmount, outAmount, isChooseable)} href="#next-step">
                { if errorMsg
                    <div className="item-content error">
                        {errorMsg}
                    </div>
                }
                <div className="item-header">
                    { transactionHeaderText }
                </div>
                <p>
                    { 
                        if isChooseable
                            <small>
                                Click the arrow to choose this swap.
                                { if changeMessage?
                                    <span className="changeMessage"> {changeMessage}</span>
                                }
                            </small>

                        else
                            <small>Enter an amount above</small>
                    }
                </p>
                <div className="icon-next"></div>
                <div className="clearfix"></div>
            </a>
        </li>


# ##############################################################################################################################
# The swap receive component

SwapbotPlaceOrder = React.createClass
    displayName: 'SwapbotPlaceOrder'

    getInitialState: ()->
        return $.extend(
            {},
            getViewState()
        )

    _onChange: ()->
        this.setState(getViewState())
        return

    componentDidMount: ()->
        UserChoiceStore.addChangeListener(this._onChange)
        QuotebotStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        UserChoiceStore.removeChangeListener(this._onChange)
        QuotebotStore.removeChangeListener(this._onChange)
        return


    getMatchingSwapConfigsForOrder: ()->
        swapConfigs = this.props.bot?.swaps
        if not swapConfigs then return []

        direction = this.state.userChoices.direction
        if direction == BotConstants.DIRECTION_SELL
            return this.getMatchingSwapConfigsForSellOrder(swapConfigs)
        else
            return this.getMatchingSwapConfigsForBuyOrder(swapConfigs)

    getMatchingSwapConfigsForSellOrder: (swapConfigs)->
        filteredSwapConfigs = []
        chosenOutAsset = this.state.userChoices.outAsset
        for otherSwapConfig, offset in swapConfigs
            if otherSwapConfig.out == chosenOutAsset and otherSwapConfig.direction == BotConstants.DIRECTION_SELL
                filteredSwapConfigs.push(otherSwapConfig)
        return filteredSwapConfigs

    getMatchingSwapConfigsForBuyOrder: (swapConfigs)->
        filteredSwapConfigs = []
        chosenInAsset = this.state.userChoices.inAsset
        for otherSwapConfig, offset in swapConfigs
            if otherSwapConfig.in == chosenInAsset and otherSwapConfig.direction == BotConstants.DIRECTION_BUY
                filteredSwapConfigs.push(otherSwapConfig)
        return filteredSwapConfigs


    onOrderInput: ()->
        # select first swap
        matchingSwapConfigs = this.getMatchingSwapConfigsForOrder()
        return if not matchingSwapConfigs

        if matchingSwapConfigs.length == 1
            UserInputActions.chooseSwapConfigAtRate(matchingSwapConfigs[0], this.state.currentBTCPrice)

        return

    render: ()->
        bot = this.props.bot
        outAsset = this.state.userChoices.outAsset
        outAmount = this.state.userChoices.outAmount
        inAsset = this.state.userChoices.inAsset
        inAmount = this.state.userChoices.inAmount
        
        showMatchingSwaps = false
        matchingSwapConfigs = this.getMatchingSwapConfigsForOrder()
        if matchingSwapConfigs?.length > 0
            if this.state.userChoices.direction == BotConstants.DIRECTION_SELL
                if outAmount? and outAmount > 0 then showMatchingSwaps = true
            if this.state.userChoices.direction == BotConstants.DIRECTION_BUY
                if inAmount? and inAmount > 0 then showMatchingSwaps = true


        # if this.state.userChoices.outAmount? and this.state.userChoices.outAmount > 0

        return <div id="swapbot-container" className="section grid-100">
            <div id="swap-step-2" className="content">
                <h2>Place Your Order</h2>
                <div className="segment-control">
                    <div className="line"></div>
                    <br />
                    <div className="dot"></div>
                    <div className="dot selected"></div>
                    <div className="dot"></div>
                    <div className="dot"></div>
                </div>

                <PlaceOrderInput onOrderInput={this.onOrderInput} bot={bot} />

                <div id="GoBackLink">
                    <a id="go-back" onClick={UserInputActions.goBackOnClick} href="#go-back" className="shadow-link">Go Back</a>

                    <NeedHelpLink botName={bot.name} />
                </div>
                
                { if showMatchingSwaps
                    <div>
                        <ul id="transaction-select-list" className="wide-list">
                            { 
                                if matchingSwapConfigs
                                    for matchedSwapConfig, offset in matchingSwapConfigs
                                            <SwapbotSendItem key={'swap' + offset} direction={this.state.userChoices.direction} inAmount={this.state.userChoices.inAmount} outAmount={this.state.userChoices.outAmount} currentBTCPrice={this.state.currentBTCPrice} swapConfig={matchedSwapConfig} bot={bot} />
                            }
                        </ul>
                        <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.formatters.confirmationsProse(bot.confirmationsRequired)}</b> and return tokens <b>to the same address</b>.</p>
                    </div>
                }

            </div>
        </div>



# #############################################
module.exports = SwapbotPlaceOrder


