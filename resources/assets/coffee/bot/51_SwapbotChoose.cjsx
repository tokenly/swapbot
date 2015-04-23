
# ############################################################################################################

SwapbotChoose = React.createClass
    displayName: 'SwapbotChoose'

    componentDidMount: ()->
        this.props.swapDetails.swap = null
        console.log "bot=",this.props.bot
        return

    buildChooseSwap: (swap)->
        return (e)=>
            e.preventDefault()
            this.props.swapDetails.swap = swap
            this.props.router.setRoute('/receive')
            return


    render: ()->
            bot = this.props.bot
            console.log "bot.swaps=",bot.swaps
            return null if not bot

            <div id="swap-step-1">
                <div className="section grid-50">
                    <h3>Description</h3>
                    <div className="description">{this.props.bot.description}</div>
                </div>
                <div className="section grid-50">
                    <h3>Available Swaps</h3>
                    <div id="SwapsListComponent">
                        {
                            if bot.swaps
                                <ul id="swaps-list" className="wide-list">
                                {
                                    for swap, index in bot.swaps
                                        <li key={"swap#{index}"} className="swap">
                                            <div>
                                                <div className="item-header">{ swap.out } <small>({bot.balances[swap.out]} available)</small></div>
                                                <p>Sends { swapbot.swapUtils.exchangeDescription(swap) }.</p>
                                                <a href="#choose-swap" onClick={this.buildChooseSwap(swap)} className="icon-next"></a>
                                            </div>
                                        </li>
                                }
                                </ul>
                            else
                                <p className="description">There are no swaps available.</p>
                        }
                    </div>
                </div>
            </div>

