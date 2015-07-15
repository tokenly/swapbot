PlaceOrderInput = null

do ()->

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
            outAmount = parseFloat($(e.target).val())
            outAmount = 0 if outAmount < 0 or isNaN(outAmount)
            UserInputActions.updateOutAmount(outAmount)
            return

        checkEnter: (e)->
            if e.keyCode == 13
                if this.props.onOrderInput?
                    this.props.onOrderInput()
            return

        render: ()->
            bot = this.props.bot
            defaultValue = this.state.userChoices.outAmount
            outAsset = this.state.userChoices.outAsset
            swapConfigIsChosen = !!this.state.userChoices.swapConfig?

            outAmount = swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])
            isChooseable = swapbot.formatters.isNotZero(bot.balances[outAsset])

            return <div>
                        {
                            if bot.state != 'active'
                                <div className="warning">
                                    <img src="/images/misc/stop.png" alt="STOP" />
                                    <p>This bot is currently inactive and needs attention by its operator. <br/> Swaps by this bot may be delayed or refunded until this is corrected.</p>
                                </div>
                            else if not isChooseable
                            # should not get here
                                <div className="warning">
                                    <img src="/images/misc/stop.png" alt="STOP" />
                                    <p>This bot is currently empty of {outAsset}. <br/> Swaps by this bot may be delayed or refunded until this is corrected.</p>
                                </div>
                        }

                        <table className="fieldset">
                            <tr>
                                <td>
                                    <label htmlFor="token-available">{outAsset} available for purchase: </label>
                                </td>
                                <td><span id="token-available">{swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])} {outAsset}</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <label htmlFor="token-amount">I would like to purchase: </label>
                                </td>
                                <td>
                                    { if swapConfigIsChosen
                                        # <input disabled type="text" id="token-amount" placeholder="0" defaultValue={defaultValue} />
                                        <div className="chosenInputAmount">
                                            {swapbot.formatters.formatCurrency(defaultValue)}
                                            &nbsp;
                                            {outAsset}
                                        </div>
                                    else
                                        <div>
                                            <input onChange={this.updateAmount} onKeyUp={this.checkEnter} type="text" id="token-amount" placeholder={'0'} defaultValue={defaultValue} />
                                            &nbsp;
                                            {outAsset}
                                        </div>
                                    }
                                </td>
                            </tr>
                        </table>
                    </div>

