# ---- begin references
UserInputActions = require '../../actions/UserInputActions'
UserInterfaceStateStore = require '../../stores/UserInterfaceStateStore'
swapbot = swapbot or {}; swapbot.formatters = require '../../../shared/formatters'
swapUtils = require '../../util/swapUtils'
# ---- end references

SwapbotChoose = null

getViewState = ()->
    return {
        ui: UserInterfaceStateStore.getUIState()
    }

# ############################################################################################################
# The swap chooser component

SwapbotChoose = React.createClass
    displayName: 'SwapbotChoose'

    getInitialState: ()->
        return getViewState()


    componentDidMount: ()->
        # this.numberOfSwapGroups = swapUtils.groupSwapConfigs(this.prop.bot.swaps).length
        UserInterfaceStateStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        UserInterfaceStateStore.removeChangeListener(this._onChange)
        return

    _onChange: ()->
        this.setState(getViewState())
        return


    buildChooseAsset: (asset, isSell, isChooseable)->
        return (e)=>
            e.preventDefault()
            if isChooseable
                UserInputActions.chooseAsset(asset, isSell)
            return


    render: ()->
        bot = this.props.bot
        return null if not bot

        swapConfigGroups = swapUtils.groupSwapConfigs(bot.swaps)

        <div id="swap-step-1">
            <div className="section grid-50">
                <h3>Description</h3>
                <div className="description" dangerouslySetInnerHTML={{__html: this.props.bot.descriptionHtml}}></div>
            </div>
            <div className="section grid-50">
                {
                    if swapConfigGroups.sell.length == 0 and swapConfigGroups.buy.length == 0
                        <div>
                            <h3>No Available Swaps</h3>
                            <div id="SwapsListComponent">
                                <p className="description">There are no swaps available at this time.</p>
                            </div>
                        </div>
                    else 
                        <div>
                            { if swapConfigGroups.sell.length > 0
                                this.renderAvailableSwaps(swapConfigGroups.sell, bot, true)
                            }
                            { if swapConfigGroups.sell.length > 0 and swapConfigGroups.buy.length > 0
                                <div className="buy-sell-separator"></div>
                            }
                            { if swapConfigGroups.buy.length > 0
                                this.renderAvailableSwaps(swapConfigGroups.buy, bot, false, swapConfigGroups.sell.length)
                            }
                        </div>

                }


            </div>
        </div>

    renderAvailableSwaps: (swapConfigGroups, bot, isSell, startingOffset=0)->
        isBuy = not isSell
        return <div>
                <h3>{ if isSell then 'Tokens for Sale' else 'Offers to Buy Tokens' }</h3>
                <div id="SwapsListComponent">
                    {
                        if bot.swaps
                            <ul id="swaps-list" className="wide-list">
                            {
                                for swapConfigGroup, index in swapConfigGroups
                                    btnIndex = index + startingOffset
                                    inAsset = swapConfigGroup[0].in

                                    outAsset = swapConfigGroup[0].out

                                    if isBuy
                                        inAmount = this.calculateBuyableAmount(bot, swapConfigGroup)
                                        inAmount = swapbot.formatters.formatCurrencyWithForcedZero(inAmount)
                                        availableMsg = "(Sell up to #{inAmount})"
                                    else
                                        outAmount = swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])
                                        availableMsg = "(#{outAmount} available)"


                                    isChooseable = swapbot.formatters.isNotZero(bot.balances[outAsset])
                                    [firstSwapDescription, otherSwapDescriptions, swapRulesSummary, whitelistSummary] = swapUtils.buildExchangeDescriptionsForGroup(bot, swapConfigGroup)

                                    <li key={"swapGroup#{index}"} className={"chooseable swap"+(" unchooseable" if not isChooseable) }>
                                        <a href="#choose-swap" onClick={this.buildChooseAsset((if isSell then outAsset else inAsset), isSell, isChooseable)}>
                                            <div>
                                                <div className="item-header">{ if isSell then "Buy #{outAsset}" else "Sell #{inAsset}" } 
                                                    {   if isChooseable
                                                            <small> {availableMsg}</small>
                                                        else
                                                            <small className="error"> OUT OF STOCK</small>
                                                    }
                                                </div>
                                                <p className="exchange-description">
                                                    This bot will send you { firstSwapDescription }.
                                                    { if otherSwapDescriptions?
                                                        <span className="line-two"><br/>{ otherSwapDescriptions }.</span>
                                                    }
                                                    { if swapRulesSummary?
                                                        <span className="line-swap-rules"><br/>{ swapRulesSummary }.</span>
                                                    }
                                                    { if whitelistSummary?
                                                        <span className="line-swap-rules whitelist-summary"><br/>{ whitelistSummary }.</span>
                                                    }
                                                </p>
                                                <div className="icon-next" style={transform: if isChooseable and this.state.ui.animatingSwapButtons[if btnIndex < 6 then btnIndex else 5] then "scale(1.4)" else "scale(1)"}></div>
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

    calculateBuyableAmount: (bot, swapConfigGroup)->
        return swapUtils.calculateMaxBuyableAmount(bot.balances, swapConfigGroup)

# #############################################
module.exports = SwapbotChoose
