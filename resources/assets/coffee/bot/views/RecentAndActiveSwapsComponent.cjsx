RecentAndActiveSwapsComponent = null
RecentOrActiveSwapComponent = null

do ()->

    getViewState = ()->
        return {
            swaps: SwapsStore.getSwaps()
        }


    # ############################################################################################################
    # An entry in the active or recent swaps list

    RecentOrActiveSwapComponent = React.createClass
        displayName: 'RecentOrActiveSwapComponent'

        getInitialState: ()->
            return {
                fromNow: null
            }

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

        render: ->
            swap = this.props.swap
            bot = this.props.bot
            
            icon = 'pending'
            if swap.isError then icon = 'failed'
            else if swap.isComplete then icon = 'confirmed'

            return <li className={icon}>
                    <div className={"status-icon icon-#{icon}"}></div>
                    <div className="status-content">
                        <span>
                        <div className="date">{this.state.fromNow}</div>
                        <span>
                            {
                                if swap.isError or not swap.isComplete
                                    swap.message
                                else
                                    "Sold #{swapbot.formatters.formatCurrency(swap.quantityOut)} #{swap.assetOut} for #{swapbot.formatters.formatCurrency(swap.quantityIn)} #{swap.assetIn}"
                            }
                            
                            { if swap.isComplete
                                <a href={"/public/#{bot.username}/swap/#{swap.id}"} className="details-link" target="_blank"><i className="fa fa-arrow-circle-right"></i></a>
                            }
                        </span>
                        { if not swap.isComplete
                            <div>
                                <small>Waiting for {swapbot.formatters.confirmationsProse(bot)} to send {swap.quantityOut} {swap.assetOut}</small>
                            </div>
                        }
                        </span>
                    </div>
                </li>


    # ############################################################################################################
    # The list of all recent or active swaps

    RecentAndActiveSwapsComponent = React.createClass
        displayName: 'RecentAndActiveSwapsComponent'

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            this.setState(getViewState())

        componentDidMount: ()->
            SwapsStore.addChangeListener(this._onChange)
            return

        componentWillUnmount: ()->
            SwapsStore.removeChangeListener(this._onChange)
            return

        activeSwaps: ()->
            activeSwaps = []
            for swap in this.state.swaps
                if not swap.isComplete
                    activeSwaps.push(swap)
            return activeSwaps

        recentSwaps: ()->
            recentSwaps = []
            for swap in this.state.swaps
                if swap.isComplete
                    recentSwaps.push(swap)
            return recentSwaps

        render: ->
            if not this.state.swaps
                return <div>No swaps</div>
            
            anyActiveSwaps = false
            anyRecentSwaps = false

            return <div>
                <div id="active-swaps" className="section grid-100">
                    <h3>Active Swaps</h3>
                    <ul className="swap-list">
                        {
                            for swap in this.activeSwaps()
                                anyActiveSwaps = true
                                <RecentOrActiveSwapComponent key={swap.id} bot={this.props.bot} swap={swap} />
                        }
                    </ul>
                    {
                        if not anyActiveSwaps
                            <div className="description">No Active Swaps</div>
                    }
                </div>
                <div className="clearfix"></div>
                <div id="recent-swaps" className="section grid-100">
                    <h3>Recent Swaps</h3>
                    <ul className="swap-list">
                        {
                            for swap in this.recentSwaps()
                                anyRecentSwaps = true
                                <RecentOrActiveSwapComponent key={swap.id} bot={this.props.bot} swap={swap} />
                        }
                    </ul>
                    {
                        if not anyRecentSwaps
                            <div className="description">No Recent Swaps</div>
                    }

                    <div style={textAlign: 'center'}>
                        <button className="button-load-more">Load more swaps...</button>
                    </div>
                </div>
            </div>


