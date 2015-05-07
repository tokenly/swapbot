SwapPurchaseStepsComponent = null

do ()->

    getViewState = ()->
        return UserChoiceStore.getUserChoices()


    # ############################################################################################################
    # The swap chooser component

    SwapPurchaseStepsComponent = React.createClass
        displayName: 'SwapPurchaseStepsComponent'

        getInitialState: ()->
            return $.extend(
                {
                },
                getViewState()
            )

        _onUserChoiceChange: ()->
            this.setState(getViewState())


        componentDidMount: ()->
            UserChoiceStore.addChangeListener(this._onUserChoiceChange)
            return

        componentWillUnmount: ()->
            UserChoiceStore.removeChangeListener(this._onUserChoiceChange)
            return

        render: ->
            <div>
            { if this.props.bot?
                <div>
                { if this.state.step == 'choose'   then <SwapbotChoose               bot={this.props.bot} /> else null }
                { if this.state.step == 'place'    then <SwapbotPlaceOrder           bot={this.props.bot} /> else null }
                { if this.state.step == 'receive'  then <SwapbotReceivingTransaction bot={this.props.bot} /> else null }
                { if this.state.step == 'wait'     then <SwapbotWait                 bot={this.props.bot} /> else null }
                { if this.state.step == 'complete' then <SwapbotComplete             bot={this.props.bot} /> else null }
                </div>
            else
                <div className="loading">Loading...</div>
            }
            </div>





