SwapbotReceive = null

do ()->

    getViewState = ()->
        return { userChoices: UserChoiceStore.getUserChoices() }

    

    # ##############################################################################################################################
    # The swap receive component

    SwapbotReceive = React.createClass
        displayName: 'SwapbotReceive'

        getInitialState: ()->
            return $.extend(
                {},
                getViewState()
            )


        getMatchingSwapConfigsForOutputAsset: ()->
            filteredSwapConfigs = []
            swapConfigs = this.props.bot?.swaps
            chosenOutAsset = this.state.userChoices.outAsset

            if swapConfigs
                for otherSwapConfig, offset in swapConfigs
                    if otherSwapConfig.out == chosenOutAsset
                        filteredSwapConfigs.push(otherSwapConfig)
            return filteredSwapConfigs

        updateAmount: (e)->
            outAmount = parseFloat($(e.target).val())
            UserInputActions.updateOutAmount(outAmount)
            return

        checkEnter: (e)->
            if e.keyCode == 13
                # select first swap
                matchingSwapConfigs = this.getMatchingSwapConfigsForOutputAsset()
                return if not matchingSwapConfigs

                if matchingSwapConfigs.length == 1
                    UserInputActions.chooseSwapConfig(matchingSwapConfigs[0])
            return

        render: ()->
            defaultValue = this.state.userChoices.outAmount
            bot = this.props.bot
            matchingSwapConfigs = this.getMatchingSwapConfigsForOutputAsset()
            outAsset = this.state.userChoices.outAsset

            return <div id="swapbot-container" className="section grid-100">
                <div id="swap-step-2" className="content">
                    <h2>Receiving transaction</h2>
                    <div className="segment-control">
                        <div className="line"></div>
                        <br />
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                    </div>
                    <table className="fieldset">
                        <tr>
                            <td>
                                <label htmlFor="token-available">{outAsset} available for purchase: </label>
                            </td>
                            <td><span id="token-available">{bot.balances[outAsset]} {outAsset}</span></td>
                        </tr>
                        <tr>
                            <td>
                                <label htmlFor="token-amount">I would like to purchase: </label>
                            </td>
                            <td>
                                <input onChange={this.updateAmount} onKeyUp={this.checkEnter} type="text" id="token-amount" placeholder={'0'} defaultValue={defaultValue} />
                                &nbsp;
                                {outAsset}
                            </td>
                        </tr>
                    </table>

                    <div id="GoBackLink">
                        <a id="go-back" onClick={UserInputActions.goBackOnClick} href="#go-back" className="shadow-link">Go Back</a>
                    </div>
                    
                    <ul id="transaction-select-list" className="wide-list">
                        { 
                            if matchingSwapConfigs
                                for matchedSwapConfig, offset in matchingSwapConfigs
                                        <SwapbotSendItem key={'swap' + offset} outAmount={this.state.userChoices.outAmount} swap={matchedSwapConfig} bot={bot} />
                        }
                    </ul>

                    <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.formatters.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
                </div>
            </div>





    # ############################################################################################################
    # A Send Item

    SwapbotSendItem = React.createClass
        displayName: 'SwapbotSendItem'

        getInAmount: ()->
            inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, this.props.swap)
            return inAmount

        isChooseable: ()->
            if this.getInAmount() > 0
                return true
            return false

        chooseSwap: (e)->
            e.preventDefault()
            return if not this.isChooseable()

            UserInputActions.chooseSwapConfig(this.props.swap)

            return

        render: ()->
            swap = this.props.swap
            inAmount = this.getInAmount()
            isChooseable = this.isChooseable()

            <li className={'choose-swap'+(if isChooseable then ' chooseable' else ' unchooseable')}>
                <a className="choose-swap" onClick={this.chooseSwap} href="#next-step">
                    <div className="item-header">Send <span id="token-value-1">{inAmount}</span> {swap.in}</div>
                    <p>
                        { 
                            if isChooseable
                                <small>Click the arrow to choose this swap</small>
                            else
                                <small>Enter an amount above</small>
                        }
                    </p>
                    <div className="icon-next"></div>
                    <div className="clearfix"></div>
                </a>
            </li>