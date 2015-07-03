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

                swapConfigGroups = swapbot.swapUtils.groupSwapConfigs(bot.swaps)

                <div id="swap-step-1">
                    <div className="section grid-50">
                        <h3>Description</h3>
                        <div className="description" dangerouslySetInnerHTML={{__html: this.props.bot.descriptionHtml}}></div>
                    </div>
                    <div className="section grid-50">
                        <h3>I want</h3>
                        <div id="SwapsListComponent">
                            {
                                if bot.swaps
                                    <ul id="swaps-list" className="wide-list">
                                    {
                                        for swapConfigGroup, index in swapConfigGroups
                                            outAsset = swapConfigGroup[0].out
                                            [firstSwapDescription, otherSwapDescriptions] = swapbot.swapUtils.buildExchangeDescriptionsForGroup(swapConfigGroup)
                                            <li key={"swapGroup#{index}"} className="chooseable swap">
                                                <a href="#choose-swap" onClick={this.buildChooseOutAsset(outAsset)}>
                                                    <div>
                                                        <div className="item-header">{ outAsset } <small>({ swapbot.formatters.formatCurrency(bot.balances[outAsset]) } available)</small></div>
                                                        <p className="exchange-description">
                                                            This bot will send you { firstSwapDescription }.
                                                            { if otherSwapDescriptions?
                                                                <span className="line-two"><br/>{ otherSwapDescriptions }.</span>
                                                            }
                                                        </p>
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

