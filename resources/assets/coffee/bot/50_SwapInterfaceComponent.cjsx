

SwapInterfaceComponent = React.createClass
    displayName: 'SwapInterfaceComponent'

    getInitialState: ()->
        return {
            step: null
            router: this.buildRouter()
            swapDetails: {
                swap: null
                chosenToken: {inAsset: null, inAmount: null, outAmount: null, }
                txInfo: null
            }
        }

    componentDidMount: ()->
        this.props.chosenSwapProvider.registerOnSwapChange (newSwap)=>

            swapDetails = this.state.swapDetails
            swapDetails.swap = newSwap
            this.setState({step: 'receive', swapDetails: swapDetails})
            return
        this.state.router.init('/choose')
        return

    render: ->
        # console.log "this.state.step=#{this.state.step}"
        <div>
        { if this.props.bot?
            <div>
            { if this.state.step == 'choose'   then <SwapbotChoose   swapDetails={this.state.swapDetails} router={this.state.router} bot={this.props.bot} /> else null }
            { if this.state.step == 'receive'  then <SwapbotReceive  swapDetails={this.state.swapDetails} router={this.state.router} bot={this.props.bot} /> else null }
            { if this.state.step == 'wait'     then <SwapbotWait     swapDetails={this.state.swapDetails} router={this.state.router} bot={this.props.bot} eventSubscriber={this.props.eventSubscriber} /> else null }
            { if this.state.step == 'complete' then <SwapbotComplete swapDetails={this.state.swapDetails} router={this.state.router} bot={this.props.bot} /> else null }
            </div>
        else
            <div className="loading">Loading...</div>
        }
        </div>


    route: (stateUpdates)->
        valid = true
        # console.log "route: ",stateUpdates
        switch stateUpdates.step
            when 'choose'
                # all good
                valid = true
                
            when 'receive', 'wait', 'complete'
                if not this.state.swapDetails.swap?
                    # no swap chosen - go back
                    valid = false
                if stateUpdates.step == 'complete'
                    if not this.state.swapDetails.txInfo?
                        # no txInfo found - go back
                        valid = false
            else
                # unknown stage
                valid = false


        if not valid
            this.state.router.setRoute('/choose')
            return

        this.setState(stateUpdates)
        return

    buildRouter: ()->
        route = this.route
        router = Router({
            '/choose'  : route.bind(this, {step: 'choose'}),
            '/receive' : route.bind(this, {step: 'receive'}),
            '/wait'    : route.bind(this, {step: 'wait'}),
            '/complete': route.bind(this, {step: 'complete'}),
        })
        return router
