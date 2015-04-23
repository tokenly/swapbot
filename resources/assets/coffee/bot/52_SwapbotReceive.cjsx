SwapbotSendItem = React.createClass
    displayName: 'SwapbotSendItem'

    chooseToken: (e)->
        e.preventDefault()
        swap = this.props.swap
        asset = swap.in
        this.props.assetWasChosen(this.props.outAmount, swap)
        return

    render: ()->
        swap = this.props.swap
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, swap)
        address = this.props.bot.address
        <li>
            <div className="item-header">Send <span id="token-value-1">{inAmount}</span> {swap.in} to</div>
            <p><a href={"bitcoin:#{address}?amount=#{inAmount}"} target="_blank">{address}</a></p>
            <a onClick={this.chooseToken} href="#next-step"><div className="icon-next"></div></a>
            <div className="icon-qr"></div>

            <img className="qr-code-image hidden" src="/images/avatars/qrcode.png" />
            <div className="clearfix"></div>
        </li>

        # ##################### NEW #####################
        # <li>
        #     <div className="item-header">Send <span id="token-value-1">0</span> BTC to</div>
        #     <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys?amount=0.1">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
        #     <a href="#open-wallet-url">
        #         <div className="icon-wallet"></div>
        #     </a>
        #     <div className="icon-qr"></div>
        #     <img className="qr-code-image hidden" src="images/avatars/qrcode.png" />
        #     <div className="clearfix"></div>
        # </li>

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
        console.log "assetWasChosen"
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(outAmount, swap)
        this.props.swapDetails.chosenToken = {
            inAsset: swap.in
            inAmount: inAmount
            outAmount: outAmount
            outAsset: swap.out
        }
        this.props.router.setRoute('/wait')
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




        # #############################################################################################

        # wait and confirm lists:

        # <ul id="transaction-wait-list" className="wide-list hidden">
        #     <li>
        #         <div className="status-icon icon-pending"></div> Waiting for <b>0.12 BTC</b> sent to <a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a>.
        #         <br /><small>Side DEMO note: when transaction is smart-guessed list will be skipped.</small>
        #     </li>
        # </ul>
        # <ul id="transaction-confirm-list" className="wide-list hidden">
        #     <li>
        #         <div className="item-content">
        #             <div className="item-header">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</div>
        #             <p>
        #                 Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br />
        #             </p>
        #         </div>
        #         <div className="item-actions">
        #             <div className="icon-next"></div>
        #         </div>
        #         <div className="clearfix"></div>
        #     </li>
        #     <li>
        #         <div className="item-content">
        #             <div className="item-header">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyy2</div>
        #             <p>
        #                 Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please.
        #                 <br /> Any data and as long as you please. YES.
        #                 <br />
        #             </p>
        #         </div>
        #         <div className="item-actions">
        #             <div className="icon-next"></div>
        #         </div>
        #         <div className="clearfix"></div>
        #     </li>
        # </ul>


        # #############################################################################################
        # OLD
        # <div id="swap-step-2" className="swap-step">
        #     <h2>Receiving transaction</h2>
        #     <div className="segment-control">
        #         <div className="line"></div><br/>
        #         <div className="dot"></div>
        #         <div className="dot selected"></div>
        #         <div className="dot"></div>
        #         <div className="dot"></div>
        #     </div>
        #     <table className="fieldset">
        #         <tr><td><label htmlFor="token-available">{swap.out} available for purchase: </label></td>
        #         <td><span id="token-available">{bot.balances[swap.out]} {swap.out}</span></td></tr>

        #         <tr><td><label htmlFor="token-amount">I would like to purchase: </label></td>
        #         <td><input onChange={this.updateAmounts} onKeyUp={this.checkEnter} type="text" id="token-amount" placeholder={'0 '+swap.out} defaultValue={this.props.swapDetails.chosenToken.outAmount} /></td></tr>
        #     </table>
        #     <ul className="wide-list">
        #         { 
        #             if this.state.matchingSwaps
        #                 for otherSwap,offset in this.state.matchingSwaps
        #                         <SwapbotSendItem key={'swap' + offset} swap={otherSwap} bot={bot} outAmount={this.state.outAmount} assetWasChosen={this.assetWasChosen} />
        #         }
        #     </ul>

        #     <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.botUtils.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
        # </div>






