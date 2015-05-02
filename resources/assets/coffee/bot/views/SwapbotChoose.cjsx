SwapbotChoose = null

do ()->


    # ############################################################################################################
    # The swap chooser component

    SwapbotChoose = React.createClass
        displayName: 'SwapbotChoose'

        getInitialState: ()->
            return {
            }

        componentDidMount: ()->
            return

        buildChooseOutAsset: (outAsset)->
            return (e)=>
                e.preventDefault()
                UserInputActions.chooseOutAsset(outAsset)
                return


        render: ()->
                bot = this.props.bot
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
                                        for swapConfig, index in bot.swaps
                                            <li key={"swapConfig#{index}"} className="chooseable swap">
                                                <a href="#choose-swap" onClick={this.buildChooseOutAsset(swapConfig.out)}>
                                                    <div>
                                                        <div className="item-header">{ swapConfig.out } <small>({bot.balances[swapConfig.out]} available)</small></div>
                                                        <p>Sends { swapbot.swapUtils.exchangeDescription(swapConfig) }.</p>
                                                        <div className="icon-next"></div>
                                                    </div>
                                                </a>
                                            </li>
                                    }
                                    </ul>
                                else
                                    <p className="description">There are no swaps available.</p>
                            }
                        </div>
                    </div>
                </div>

