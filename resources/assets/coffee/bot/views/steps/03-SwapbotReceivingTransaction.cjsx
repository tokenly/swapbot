SwapbotReceivingTransaction = null

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

            addressCopied: false
        }
    

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
            ts = if this.props.swap.completedAt? then this.props.swap.completedAt else this.props.swap.updatedAt
            this.setState({fromNow: moment(ts).fromNow()})
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
            # console.log "chooseSwap"
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
                        <p>This transaction has <b>{swap.confirmations} out of {bot.confirmationsRequired}</b> {swapbot.formatters.confirmationsWord(bot)}.</p>
                    </div>
                    <div className="item-actions">
                        <div className="icon-next"></div>
                    </div>
                </a>
                <div className="clearfix"></div>
            </li>



    # ########################################################################################################################
    # The swapbot wait receive component

    SwapbotReceivingTransaction = React.createClass
        displayName: 'SwapbotReceivingTransaction'
        copiedTimeoutRef: null

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            # console.log "SwapbotReceivingTransaction _onChange.  "
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

        onAfterCopy: () ->
            this.setState({addressCopied: true})

            if this.copiedTimeoutRef? then clearTimeout(this.copiedTimeoutRef)

            this.copiedTimeoutRef = setTimeout ()=>
                this.setState({addressCopied: false})
                this.copiedTimeoutRef = null
            , 2500

            return

        render: ()->
            # console.log "SwapbotReceivingTransaction render"
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

                    <PlaceOrderInput bot={bot} />

                    <div className="sendInstructions">
                        To begin this swap, send <strong>{swapbot.formatters.formatCurrency(this.state.userChoices.inAmount)} {this.state.userChoices.inAsset}</strong> to {bot.address}
                        <ReactZeroClipboard 
                            text={bot.address}
                            onAfterCopy={this.onAfterCopy}
                        >
                           <button className={"copyToClipboard"+(if this.state.addressCopied then ' copied' else '')} title="copy to clipboard"><i className="fa fa-clipboard"></i> {if this.state.addressCopied then 'Copied' else 'Copy'}</button>
                        </ReactZeroClipboard>

                    </div>

                    <div id="GoBackLink">
                        <a id="go-back" onClick={UserInputActions.goBackOnClick} href="#go-back" className="shadow-link">Go Back</a>
                    </div>


                    {
                            if this.state.anyMatchedSwaps
                                <div>
                                    <h4>We&rsquo;ve detected one or multiple orders that might be yours, please select the correct one to continue.</h4>
                                    <ul id="transaction-confirm-list" className="wide-list">
                                        {
                                            for swap in this.state.matchedSwaps
                                                <TransactionInfo key={swap.id} bot={bot} swap={swap} />
                                        }
                                    </ul>
                                </div>
                            else
                                <div>
                                    <ul id="transaction-wait-list" className="wide-list">
                                        <li>
                                            <div className="status-icon icon-pending"></div>
                                            Waiting for <strong>{swapbot.formatters.formatCurrency(this.state.userChoices.inAmount)} {this.state.userChoices.inAsset}</strong> to be sent to {bot.address}
                                            <br/>
                                        </li>
                                    </ul>
                                </div>
                    }



                    <p className="description">After receiving one of those token types, this bot will wait for <b>{swapbot.formatters.confirmationsProse(bot)}</b> and return tokens <b>to the same address</b>.</p>
                </div>
            </div>
