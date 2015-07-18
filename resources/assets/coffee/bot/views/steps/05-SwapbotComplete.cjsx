SwapbotComplete = null

do ()->

    getViewState = ()->
        userChoices = UserChoiceStore.getUserChoices()
        swaps = SwapsStore.getSwaps()

        return {
            userChoices: userChoices
            swaps      : swaps
        }
    

    # ########################################################################################################################
    # The swapbot wait receive component

    SwapbotComplete = React.createClass
        displayName: 'SwapbotComplete'

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            # console.log "SwapbotComplete _onChange.  "
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


        notMyTransactionClicked: (e)->
            e.preventDefault()
            UserInputActions.clearSwap()
            return

        closeClicked: (e)->
            e.preventDefault()
            UserInputActions.resetSwap()
            return


        # ########################################################################


        render: ()->
            # console.log "SwapbotComplete render"
            bot = this.props.bot
            swap = this.state.userChoices.swap
            return null if not swap

            return <div id="swapbot-container" className="section grid-100">
                <div id="swap-step-4" className="content">
                    <h2>Success!</h2>
                    <a href="#close" onClick={this.closeClicked} className="x-button" id="swap-step-4-close"></a>
                    <h3 className="subtitle">Swap Completed</h3>
                    <div className="segment-control">
                        <div className="line"></div>
                        <br />
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot"></div>
                        <div className="dot selected"></div>
                    </div>
                    <div className="icon-success center"></div>
                    <p>Thank you for swapping with <a href={swapbot.addressUtils.publicBotHrefFromBot(bot)}>{bot.name}</a>!</p>
                    <p>
                        {swapbot.eventMessageUtils.fullSwapSummary(swap, bot)}
                        <br/>
                        <a id="not-my-transaction" onClick={this.notMyTransactionClicked} href="#" className="shadow-link">Not your transaction?</a>
                    </p>
                    <p><a href={"/public/#{bot.username}/swap/#{swap.id}"} className="details-link" target="_blank">Transaction details <i className="fa fa-arrow-circle-right"></i></a></p>
                </div>
            </div>


    # ########################################################################################################################

