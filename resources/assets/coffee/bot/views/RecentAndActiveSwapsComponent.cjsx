# ---- begin references
UserInterfaceActions = require '../actions/UserInterfaceActions'
BotStore = require '../stores/BotStore'
SwapsStore = require '../stores/SwapsStore'
UserInterfaceStateStore = require '../stores/UserInterfaceStateStore'
swapbot = swapbot or {}; swapbot.eventMessageUtils = require '../../shared/eventMessageUtils'
swapbot = swapbot or {}; swapbot.formatters = require '../../shared/formatters'
swapbot = swapbot or {}; swapbot.addressUtils = require '../../shared/addressUtils'
# ---- end references

RecentAndActiveSwapsComponent = null
RecentOrActiveSwapComponent = null


getViewState = (botId)->
    return {
        bot: BotStore.getBot(botId)
        swaps: SwapsStore.getSwaps()
        swapsUI: UserInterfaceStateStore.getSwapsUIState()
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
        else if swap.isComplete and swap.type == 'refund' then icon = 'refunded'
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
                                else if swap.type == 'refund'
                                    "Refunded #{swapbot.formatters.formatCurrency(swap.quantityOut)} #{swap.assetOut}"
                                else
                                    "Sold #{swapbot.formatters.formatCurrency(swap.quantityOut)} #{swap.assetOut} for #{swapbot.formatters.formatCurrency(swap.quantityIn)} #{swap.assetIn}"
                            }
                            
                            { if swap.isComplete
                                <a href={swapbot.addressUtils.publicSwapHref(swap, bot.username)} className="details-link" target="_blank"><i className="fa fa-arrow-circle-right"></i></a>
                            }
                        </span>
                        { if not swap.isComplete
                            <div>
                                <small>{swapbot.eventMessageUtils.buildSwapStatusMessageElement(swap, bot)}</small>
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
        return getViewState(this.props.botid)

    _onChange: ()->
        this.setState(getViewState(this.props.botid))

    componentDidMount: ()->
        BotStore.addChangeListener(this._onChange)
        SwapsStore.addChangeListener(this._onChange)
        UserInterfaceStateStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        BotStore.removeChangeListener(this._onChange)
        SwapsStore.removeChangeListener(this._onChange)
        UserInterfaceStateStore.removeChangeListener(this._onChange)
        return

    buildRecentAndActiveSwapComponents: (limit=999)->
        activeSwaps = []
        recentSwaps = []


        for swap, index in this.state.swaps
            isRecent = false
            isVisible = true
            if swap.isComplete
                isRecent = true
            switch swap.state
                when 'permanenterror'
                    isRecent = true
                when 'invalidated'
                    isVisible = false

            if not isVisible then continue

            if isRecent
                recentSwaps.push(<RecentOrActiveSwapComponent key={"ra-"+swap.id} bot={this.state.bot} swap={swap} />)
            else
                activeSwaps.push(<RecentOrActiveSwapComponent key={"ra-"+swap.id} bot={this.state.bot} swap={swap} />)
        
            if index >= limit - 1
                break

        return [activeSwaps, recentSwaps]

    updateMaxSwapsToShow: (e)->
        e.preventDefault()
        UserInterfaceActions.updateMaxSwapsToShow()
        return

    render: ()->
        if not this.state.swaps
            return <div>No swaps</div>


        swapsUI = this.state.swapsUI
        [activeSwaps, recentSwaps] = this.buildRecentAndActiveSwapComponents(swapsUI.maxSwapsToShow)


        # ------ Active Swaps ------ #

        activeSwapsSection = 
            <div id="active-swaps" className="section grid-100">
                <h3>Active Swaps</h3>
                <ul className="swap-list">{activeSwaps}</ul>
                {
                    if not activeSwaps.length
                        <div className="description">No Active Swaps</div>
                }
            </div>


        # ------ Recent Swaps ------ #

        if activeSwaps.length >= swapsUI.maxSwapsToShow
            recentSwapsSection = null
        else
            recentSwapsSection =
                <div id="recent-swaps" className="section grid-100">
                    <h3>Recent Swaps</h3>
                    <ul className="swap-list">{recentSwaps}</ul>
                    {
                        if not recentSwaps.length
                            <div className="description">No Recent Swaps</div>
                    }
                </div>


        # ------ Load More ------ #

        if swapsUI.loading
            loadMoreButton = 
                <div style={textAlign: 'center'}>
                    <button disabled="disabled" className="button-load-more">Loading...</button>
                </div>
        else
            if swapsUI.maxSwapsToShow > swapsUI.numberOfSwapsLoaded
                loadMoreButton = null
            else
                loadMoreButton = 
                    <div style={textAlign: 'center'}>
                        <button onClick={this.updateMaxSwapsToShow} className="button-load-more">Load more swaps</button>
                    </div>


        # ------ Combined ------ #

        return  <div>
                    { activeSwapsSection }
                    <div className="clearfix"></div>
                    { recentSwapsSection }
                    { loadMoreButton }
                </div>


# #############################################
module.exports = RecentAndActiveSwapsComponent
