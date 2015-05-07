SwapbotWait = null

do ()->

    getViewState = ()->
        userChoices = UserChoiceStore.getUserChoices()

        return {
            userChoices  : userChoices
        }
    

    # ########################################################################################################################

    SingleTransactionInfo = React.createClass
        displayName: 'SingleTransactionInfo'
        intervalTimer: null

        componentDidMount: ()->
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

            # Received <b>{swap.quantityIn} {swap.assetIn}</b> from {swap.destination}.

            return <div>

                    <p>
                        {swap.message}<br/>
                        This transaction has <b>{swapbot.formatters.formatConfirmations(swap.confirmations)} of {bot.confirmationsRequired}</b> {swapbot.formatters.confirmationsWord(bot)} in and <b>{swapbot.formatters.formatConfirmations(swap.confirmationsOut)}</b> {swapbot.formatters.confirmationsWord(bot)} out.<br/>
                        <a id="not-my-transaction" onClick={this.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                    </p>

                    <p>&nbsp;</p>

                    { if userChoices.email.emailErrorMsg then <p className="error">{userChoices.email.emailErrorMsg}  Please try again.</p> else null }

                    {
                        if userChoices.email.submittedEmail
                            <p>
                                <strong>Email address submitted.</strong>  Please check your email.
                            </p>
                        else
                            <div>
                            <p>
                                Don’t want to wait here?
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
                            </div>
                    }
                </div>


    # ########################################################################################################################
    # The swapbot wait receive component

    SwapbotWait = React.createClass
        displayName: 'SwapbotWait'

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            # console.log "SwapbotWait _onChange.  "
            this.setState(getViewState())
            return

        componentDidMount: ()->
            UserChoiceStore.addChangeListener(this._onChange)
            return

        componentWillUnmount: ()->
            UserChoiceStore.removeChangeListener(this._onChange)
            return


        # ########################################################################


        render: ()->
            # console.log "SwapbotWait render"
            bot = this.props.bot
            swapConfig = this.state.userChoices.swapConfig
            defaultValue = this.state.userChoices.outAmount
            outAsset = this.state.userChoices.outAsset
            return null if not swapConfig

            return <div id="swapbot-container" className="section grid-100">
                <div id="swap-step-3" className="content">
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

                    <div className="chosenInputAmount">
                        Purchasing
                        {' '+swapbot.formatters.formatCurrency(defaultValue)}
                        &nbsp;
                        {outAsset}
                    </div>

                    {
                        if this.state.userChoices.swap?
                            <SingleTransactionInfo bot={bot} userChoices={this.state.userChoices} />
                        else
                            <div>No transaction found</div>
                    }



                    <p className="description">This bot will wait for <b>{swapbot.formatters.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
                </div>
            </div>

