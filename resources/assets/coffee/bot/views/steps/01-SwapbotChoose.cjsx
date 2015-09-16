# ---- begin references
UserInputActions = require '../../actions/UserInputActions'
UserInterfaceStateStore = require '../../stores/UserInterfaceStateStore'
swapbot = swapbot or {}; swapbot.formatters = require '../../../shared/formatters'
swapbot = swapbot or {}; swapbot.swapUtils = require '../../../shared/swapUtils'
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
        # this.numberOfSwapGroups = swapbot.swapUtils.groupSwapConfigs(this.prop.bot.swaps).length
        UserInterfaceStateStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        UserInterfaceStateStore.removeChangeListener(this._onChange)
        return

    _onChange: ()->
        this.setState(getViewState())
        return


    buildChooseOutAsset: (outAsset, isChooseable)->
        return (e)=>
            e.preventDefault()
            if isChooseable
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
                                    outAmount = swapbot.formatters.formatCurrencyWithForcedZero(bot.balances[outAsset])
                                    isChooseable = swapbot.formatters.isNotZero(bot.balances[outAsset])
                                    [firstSwapDescription, otherSwapDescriptions] = swapbot.swapUtils.buildExchangeDescriptionsForGroup(swapConfigGroup)

                                    <li key={"swapGroup#{index}"} className={"chooseable swap"+(" unchooseable" if not isChooseable) }>
                                        <a href="#choose-swap" onClick={this.buildChooseOutAsset(outAsset, isChooseable)}>
                                            <div>
                                                <div className="item-header">{ outAsset } 
                                                    {   if isChooseable
                                                            <small> ({ outAmount } available)</small>
                                                        else
                                                            <small className="error"> OUT OF STOCK</small>
                                                    }
                                                </div>
                                                <p className="exchange-description">
                                                    This bot will send you { firstSwapDescription }.
                                                    { if otherSwapDescriptions?
                                                        <span className="line-two"><br/>{ otherSwapDescriptions }.</span>
                                                    }
                                                </p>
                                                <div className="icon-next" style={transform: if isChooseable and this.state.ui.animatingSwapButtons[if index < 6 then index else 5] then "scale(1.4)" else "scale(1)"}></div>
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


# #############################################
module.exports = SwapbotChoose
