# ---- begin references
UserInputActions = require '../../actions/UserInputActions'
UserChoiceStore = require '../../stores/UserChoiceStore'
BotConstants = require '../../constants/BotConstants'
swapbot = swapbot or {}; swapbot.formatters = require '../../../shared/formatters'
swapbot = swapbot or {}; swapbot.swapUtils = require '../../../shared/swapUtils'
# ---- end references

PlaceOrderInput = null


getViewState = ()->
    return { userChoices: UserChoiceStore.getUserChoices() }


# ##############################################################################################################################
# The place order input component

PlaceOrderInput = React.createClass
    displayName: 'PlaceOrderInput'

    getInitialState: ()->
        return $.extend(
            {},
            getViewState()
        )

    updateAmount: (e)->
        newAmount = parseFloat($(e.target).val())
        newAmount = 0 if newAmount < 0 or isNaN(newAmount)

        direction = this.state.userChoices.direction
        if direction == BotConstants.DIRECTION_SELL
            UserInputActions.updateSwapAmount(newAmount, 'out')
        else
            UserInputActions.updateSwapAmount(newAmount, 'in')

        return

    checkEnter: (e)->
        if e.keyCode == 13
            if this.props.onOrderInput?
                this.props.onOrderInput()
        return

    render: ()->
        bot = this.props.bot
        outAsset = this.state.userChoices.outAsset
        inAsset = this.state.userChoices.inAsset
        direction = this.state.userChoices.direction
        isSell = (direction == BotConstants.DIRECTION_SELL)
        isBuy = not isSell
        sellingOrBuyingAsset = (if isSell then outAsset else inAsset)
        if isBuy
            swapConfigGroups = swapbot.swapUtils.getBuySwapConfigsByInAsset(bot.swaps, inAsset)
            maxBuyableAmount = swapbot.formatters.formatCurrencyWithForcedZero(swapbot.swapUtils.calculateMaxBuyableAmount(bot.balances, swapConfigGroups))
            sellingOrBuyingAmount = this.state.userChoices.inAmount
        else
            sellingOrBuyingAmount = this.state.userChoices.outAmount

        swapConfigIsChosen = !!this.state.userChoices.swapConfig?

        outAmount = swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])
        isChooseable = swapbot.formatters.isNotZero(bot.balances[outAsset])

        return <div>
                    {
                        if bot.state == 'shuttingDown'
                            <div className="warning">
                                <img src="/images/misc/stop.png" alt="STOP" />
                                <p>This bot is currently shutting down. <br/> Swaps by this bot will not be processed.</p>
                            </div>
                        else if bot.state != 'active'
                            <div className="warning">
                                <img src="/images/misc/stop.png" alt="STOP" />
                                <p>This bot is currently inactive and needs attention by its operator. <br/> Swaps by this bot may be delayed or refunded until this is corrected.</p>
                            </div>
                        else if isSell and not isChooseable
                        # should not get here
                            <div className="warning">
                                <img src="/images/misc/stop.png" alt="STOP" />
                                <p>This bot is currently empty of {outAsset}. <br/> Swaps by this bot may be delayed or refunded until this is corrected.</p>
                            </div>
                    }

                    <table className="fieldset">
                        { if isSell
                            <tr>
                                <td>
                                    <label htmlFor="token-available">{outAsset} available for purchase: </label>
                                </td>
                                <td><span id="token-available">{swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])} {outAsset}</span></td>
                            </tr>
                          else
                            <tr>
                                <td>
                                    <label htmlFor="token-available">{inAsset} available to buy: </label>
                                </td>
                                <td><span id="token-available">{maxBuyableAmount} {inAsset}</span></td>
                            </tr>
                        }
                        <tr>
                            <td>
                                { 
                                    if isSell
                                        <label htmlFor="token-amount">I would like to purchase: </label>
                                    else
                                        <label htmlFor="token-amount">I would like to sell: </label>
                                }
                            </td>
                            <td>
                                { if swapConfigIsChosen
                                    <div className="chosenInputAmount">
                                        {swapbot.formatters.formatCurrency(sellingOrBuyingAmount)}
                                        &nbsp;
                                        {sellingOrBuyingAsset}
                                    </div>
                                else
                                    <div>
                                        <input onChange={this.updateAmount} onKeyUp={this.checkEnter} type="text" id="token-amount" placeholder="0" defaultValue={sellingOrBuyingAmount} />
                                        &nbsp;
                                        {sellingOrBuyingAsset}
                                    </div>
                                }
                            </td>
                        </tr>
                    </table>
                </div>

# #############################################
module.exports = PlaceOrderInput
