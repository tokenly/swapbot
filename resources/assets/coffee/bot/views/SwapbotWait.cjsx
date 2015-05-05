SwapbotWait = null

do ()->

    getViewState = ()->
        userChoices = UserChoiceStore.getUserChoices()
        swaps = SwapsStore.getSwaps()
        matchedSwaps = SwapMatcher.buildMatchedSwaps(swaps, userChoices)

        return {
            userChoices  : userChoices
            swaps        : swaps
            matchedSwaps : matchedSwaps
            anyMatchedSwaps: (if matchedSwaps.length > 0 then true else false)
        }
    

    # ########################################################################################################################
    # The swapbot wait receive component

    SwapbotWait = React.createClass
        displayName: 'SwapbotWait'

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            console.log "SwapbotWait _onChange.  "
            this.setState(getViewState())
            return

        componentDidMount: ()->
            SwapsStore.addChangeListener(this._onChange)
            UserChoiceStore.addChangeListener(this._onChange)
            return

        componentWillUnmount: ()->
            SwapsStore.removeChangeListener(this._onChange)
            UserChoiceStore.removeChangeListener(this._onChange)
            return


        # ########################################################################


        render: ()->
            console.log "SwapbotWait render"
            bot = this.props.bot
            swapConfig = this.state.userChoices.swapConfig
            return null if not swapConfig

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
                                <label htmlFor="token-available">{swapConfig.out} available for purchase: </label>
                            </td>
                            <td><span id="token-available">{bot.balances[swapConfig.out]} {swapConfig.out}</span></td>
                        </tr>
                        <tr>
                            <td>
                                <label htmlFor="token-amount">I would like to purchase: </label>
                            </td>
                            <td>
                                <input disabled type="text" id="token-amount" placeholder={'0 '+swapConfig.out} defaultValue={this.state.userChoices.outAmount} />
                            </td>
                        </tr>
                    </table>

                    <div id="GoBackLink">
                        <a id="go-back" onClick={UserInputActions.goBackOnClick} href="#go-back" className="shadow-link">Go Back</a>
                    </div>


                    {
                        if this.state.userChoices.swap?
                            <SingleTransactionInfo bot={bot} userChoices={this.state.userChoices} />
                        else
                            if this.state.anyMatchedSwaps
                                <ul id="transaction-confirm-list" className="wide-list">
                                    {
                                        for swap in this.state.matchedSwaps
                                            <TransactionInfo key={swap.id} bot={bot} swap={swap} />
                                    }
                                </ul>
                            else
                                <ul id="transaction-wait-list" className="wide-list">
                                    <li>
                                        <div className="status-icon icon-pending"></div>
                                        Waiting for <strong>{this.state.userChoices.inAmount} {this.state.userChoices.inAsset}</strong> to be sent to {bot.address}.
                                        <br/>
                                    </li>
                                </ul>
                    }



                    <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.botUtils.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
                </div>
            </div>

    # ########################################################################################################################

    TransactionInfo = React.createClass
        displayName: 'TransactionInfo'
        intervalTimer: null

        componentDidMount: ()->
            this.updateNow()

            this.intervalTimer = setInterval ()=>
                this.updateNow()
            , 1000

            return

        updateNow: ()->
            this.setState({fromNow: moment(this.props.swap.updatedAt).fromNow()})
            return

        componentWillUnmount: ()->
            if this.intervalTimer?
                clearInterval(this.intervalTimer)
            return

        getInitialState: ()->
            return {
                fromNow: ''
            }

        clickedFn: (e)->
            e.preventDefault()
            console.log "chooseSwap"
            UserInputActions.chooseSwap(this.props.swap)
            return

        render: ()->
            swap = this.props.swap
            bot = this.props.bot

            return <li className="chooseable">
                <a onClick={this.clickedFn} href="#choose">
                    <div className="item-content">
                        <div className="item-header" title="{swap.name}">Transaction Received</div>
                        <p className="date">{ this.state.fromNow }</p>
                        <p>{swap.message}</p>
                        <p>This transaction has <b>{swap.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                    </div>
                    <div className="item-actions">
                        <div className="icon-next"></div>
                    </div>
                </a>
                <div className="clearfix"></div>
            </li>


    # ########################################################################################################################

    SingleTransactionInfo = React.createClass
        displayName: 'SingleTransactionInfo'
        intervalTimer: null

        componentDidMount: ()->
            # this.updateNow()

            # this.intervalTimer = setInterval ()=>
            #     this.updateNow()
            # , 1000

            return

        updateNow: ()->
            # this.setState({fromNow: moment(this.props.userChoices.swap.updatedAt).fromNow()})
            return

        componentWillUnmount: ()->
            if this.intervalTimer?
                clearInterval(this.intervalTimer)
            return

        getInitialState: ()->
            return {
                # fromNow: ''
            }

        updateEmailValue: (e)->
            e.preventDefault()
            UserInputActions.updateEmailValue(e.target.value)
            return

        submitEmailFn: (e)->
            e.preventDefault()

            email = this.props.userChoices.email.value
            return if email.length < 1

            UserInputActions.submitEmail()


        notMyTransactionClicked: (e)->
            e.preventDefault()

            UserInputActions.clearSwap()


            return

        render: ()->
            userChoices = this.props.userChoices
            swap = userChoices.swap
            bot = this.props.bot
            emailValue = userChoices.email.value

            return <div id="swap-step-3" className="content">
                    <h2>Waiting for confirmations</h2>
                    <div className="segment-control">
                        <div className="line"></div>
                        <br/>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                        <div className="dot"></div>
                    </div>
                    <div className="icon-loading center"></div>
                    <p>
                        Received <b>{swap.quantityIn} {swap.assetIn}</b> from {swap.destination}.
                        <br/>
                        <a id="not-my-transaction" onClick={this.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                    </p>
                    <p>This transaction has <b>{swap.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.botUtils.confirmationsWord(bot)}.</p>
                    { if userChoices.email.emailErrorMsg then <p className="error">{userChoices.email.emailErrorMsg}  Please try again.</p> else null }
                    {
                        if userChoices.email.submittedEmail
                            <p>
                                <strong>Email address submitted.</strong>  Please check your email.
                            </p>
                        else
                            <p>
                                Don&#38;t want to wait here?
                                <br/>We can notify you when the transaction is done!
                            </p>
                            <form action="#submit-email" onSubmit={this.submitEmailFn} style={if userChoices.email.submittingEmail then {opacity: 0.2} else null}>
                                <table className="fieldset fieldset-other">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input disabled={if userChoices.email.submittingEmail then true else false} required type="email" onChange={this.updateEmailValue} id="other-address" placeholder="example@example.com" value={emailValue} />
                                            </td>
                                            <td>
                                                <div id="icon-other-next" className="icon-next" onClick={this.submitEmailFn}></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                    }
                </div>

                # destination
                # quantityIn
                # assetIn
                # txidIn
                # quantityOut
                # assetOut
                # txidOut
                # confirmations
                # state
                # isComplete
                # isError


