SwapbotSendItem = React.createClass
    displayName: 'SwapbotSendItem'

    getInAmount: ()->
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, this.props.swap)
        inAmount = 0 if inAmount == NaN
        return inAmount

    getIsChooseable: ()->
        if this.getInAmount() > 0
            return true
        return false

    chooseToken: (e)->
        e.preventDefault()
        return if not this.getIsChooseable()

        swap = this.props.swap
        asset = swap.in
        this.props.assetWasChosen(this.props.outAmount, swap)
        return

    render: ()->
        swap = this.props.swap
        inAmount = this.getInAmount()
        isChooseable = this.getIsChooseable()
        address = this.props.bot.address

        <li className={'choose-swap'+(if isChooseable then ' chooseable' else ' unchooseable')}>
            <a className="choose-swap" onClick={this.chooseToken} href="#next-step">
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

# ##############################################################################################################################

SwapbotReceive = React.createClass
    displayName: 'SwapbotReceive'

    getInitialState: ()->
        return {
            outAmount: if this.props.swapDetails.chosenToken.outAmount? then this.props.swapDetails.chosenToken.outAmount else 0
            matchingSwaps: this.getMatchingSwapsForOutputAsset()
        }

    getMatchingSwapsForOutputAsset: ()->
        filteredSwaps = []
        swaps = this.props.bot?.swaps
        swap = this.props.swapDetails.swap
        if swaps
            for otherSwap,offset in swaps
                if otherSwap.out == swap.out
                    filteredSwaps.push(otherSwap)
        return filteredSwaps

    assetWasChosen: (outAmount, swap)->
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(outAmount, swap)
        this.props.swapDetails.chosenToken = {
            inAsset: swap.in
            inAmount: inAmount
            outAmount: outAmount
            outAsset: swap.out
        }
        this.props.router.setRoute('/wait')
        return


    goBack: (e)->
        e.preventDefault();
        this.props.router.setRoute('/choose')
        return

    updateAmounts: (e)->
        outAmount = parseFloat($(e.target).val())
        this.setState({outAmount: outAmount})
        this.props.swapDetails.chosenToken.outAmount = outAmount

    checkEnter: (e)->
        if e.keyCode == 13
            # select first swap
            swaps = this.state.matchingSwaps
            return if not swaps
            if swaps.length == 1
                this.assetWasChosen(this.state.outAmount, swaps[0])
        return

    render: ()->
        swap = this.props.swapDetails.swap
        bot = this.props.bot

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
                            <label htmlFor="token-available">{swap.out} available for purchase: </label>
                        </td>
                        <td><span id="token-available">{bot.balances[swap.out]} {swap.out}</span></td>
                    </tr>
                    <tr>
                        <td>
                            <label htmlFor="token-amount">I would like to purchase: </label>
                        </td>
                        <td>
                            <input onChange={this.updateAmounts} onKeyUp={this.checkEnter} type="text" id="token-amount" placeholder={'0 '+swap.out} defaultValue={this.props.swapDetails.chosenToken.outAmount} />
                        </td>
                    </tr>
                </table>

                <div id="GoBackLink">
                    <a id="go-back" onClick={this.goBack} href="#go-back" className="shadow-link">Go Back</a>
                </div>
                
                <ul id="transaction-select-list" className="wide-list">
                    { 
                        if this.state.matchingSwaps
                            for otherSwap,offset in this.state.matchingSwaps
                                    <SwapbotSendItem key={'swap' + offset} swap={otherSwap} bot={bot} outAmount={this.state.outAmount} assetWasChosen={this.assetWasChosen} />
                    }
                </ul>

                <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.botUtils.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
            </div>
        </div>







