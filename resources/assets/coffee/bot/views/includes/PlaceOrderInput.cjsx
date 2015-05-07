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
            outAmount = 0 if outAmount < 0 or outAmount == NaN
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

            return <div>
                        <table className="fieldset">
                            <tr>
                                <td>
                                    <label htmlFor="token-available">{outAsset} available for purchase: </label>
                                </td>
                                <td><span id="token-available">{swapbot.formatters.formatCurrency(bot.balances[outAsset])} {outAsset}</span></td>
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


                    # <table className="fieldset">
                    #     <tr>
                    #         <td>
                    #             <label htmlFor="token-available">{swapConfig.out} available for purchase: </label>
                    #         </td>
                    #         <td><span id="token-available">{bot.balances[swapConfig.out]} {swapConfig.out}</span></td>
                    #     </tr>
                    #     <tr>
                    #         <td>
                    #             <label htmlFor="token-amount">I would like to purchase: </label>
                    #         </td>
                    #         <td>
                    #             <input disabled type="text" id="token-amount" placeholder={'0 '+swapConfig.out} defaultValue={this.state.userChoices.outAmount} />
                    #         </td>
                    #     </tr>
                    # </table>
