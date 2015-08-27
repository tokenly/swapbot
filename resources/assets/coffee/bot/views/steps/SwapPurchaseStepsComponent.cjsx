SwapPurchaseStepsComponent = null

do ()->

    getViewState = (botId)->
        state = UserChoiceStore.getUserChoices()
        state.bot = BotStore.getBot(botId)
        return state



    # ############################################################################################################
    # The swap chooser component

    SwapPurchaseStepsComponent = React.createClass
        displayName: 'SwapPurchaseStepsComponent'

        getInitialState: ()->
            return getViewState(this.props.botid)

        _onChange: ()->
            this.setState(getViewState(this.props.botid))


        componentDidMount: ()->
            UserChoiceStore.addChangeListener(this._onChange)
            BotStore.addChangeListener(this._onChange)
            return

        componentWillUnmount: ()->
            UserChoiceStore.removeChangeListener(this._onChange)
            BotStore.removeChangeListener(this._onChange)
            return

        render: ->
            <div>
            { if this.state.bot?
                <div>
                { if this.state.step == 'choose'         then <SwapbotChoose               bot={this.state.bot} /> else null }
                { if this.state.step == 'place'          then <SwapbotPlaceOrder           bot={this.state.bot} /> else null }
                { if this.state.step == 'confirmwallet'  then <SwapbotConfirmWallet        bot={this.state.bot} /> else null }
                { if this.state.step == 'receive'        then <SwapbotReceivingTransaction bot={this.state.bot} /> else null }
                { if this.state.step == 'wait'           then <SwapbotWait                 bot={this.state.bot} /> else null }
                { if this.state.step == 'complete'       then <SwapbotComplete             bot={this.state.bot} /> else null }
                </div>
            else
                <div className="loading">Loading...</div>
            }
            </div>





