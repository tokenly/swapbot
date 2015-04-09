

SwapbotComponent = React.createClass
    displayName: 'SwapbotComponent'

    getInitialState: ()->
        return {
            bot: null
            botId: null
            step: 'choose'
            router: this.buildRouter()
            swapDetails: {
                swap: null
                chosenToken: {inAsset: null, inAmount: null, outAmount: null, }
                txInfo: null
            }
        }

    componentDidMount: ()->
        containerEl = jQuery(this.getDOMNode()).parent()
        botId = containerEl.data('bot-id')
        this.setState({botId: botId})
        $.get "/api/v1/public/bot/#{botId}", (data)=>
            if this.isMounted()
                # console.log "data",data
                this.setState({bot: data})
            return


        this.state.router.init('/choose')
        return

    render: ->
        # console.log "this.state.step=#{this.state.step}"
        <div className={"swapbot-container " + if this.props.showing? then '' else 'hidden'}>
            <div className="header">
                <div className="avatar">
                    <img src={if this.state.bot?.hash then "http://robohash.org/#{this.state.bot.hash}.png?set=set3" else ''} />
                </div>
                <div className="status-dot bckg-green"></div>
                <h1><a href="http://raburski.com/swapbot0" target="_blank">{this.state.bot?.name}</a></h1>
            </div>
            <div className="content">
                { if this.state.bot?
                    <div>
                    { if this.state.step == 'choose' then <SwapbotChoose swapDetails={this.state.swapDetails} router={this.state.router} bot={this.state.bot} /> else null }
                    { if this.state.step == 'receive' then <SwapbotReceive swapDetails={this.state.swapDetails} router={this.state.router} bot={this.state.bot} /> else null }
                    { if this.state.step == 'wait' then <SwapbotWait swapDetails={this.state.swapDetails} router={this.state.router} bot={this.state.bot} /> else null }
                    { if this.state.step == 'complete' then <SwapbotComplete swapDetails={this.state.swapDetails} router={this.state.router} bot={this.state.bot} /> else null }
                    </div>
                else
                    <div className="loading">Loading...</div>
                }

                <div className="footer">powered by <a href="http://swapbot.co/" target="_blank">Swapbot</a></div>
            </div>
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
