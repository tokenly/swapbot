SwapbotChooseItem = React.createClass
    displayName: 'SwapbotChooseItem'

    clickSwap: (e)->
        e.preventDefault()
        this.props.chooseSwapFn(this.props.swap)
        return

    render: ()->
        swap = this.props.swap
        bot = this.props.bot
        rateDesc = swapbot.swapUtils.exchangeDescription(swap)
        <li>
            <a onClick={this.clickSwap} href="#choose">
                <div className="item-header">{swap.out} <small>({bot.balances[swap.out]} available)</small></div>
                <p>Sends {rateDesc}.</p>
                <div className="icon-next"></div>
            </a>
        </li>

SwapbotChoose = React.createClass
    displayName: 'SwapbotChoose'

    chooseSwapFn: (swap)->
        # console.log "token out "+swap.out
        this.props.swapDetails.swap = swap
        this.props.router.setRoute('/receive')

    componentDidMount: ()->
        this.props.swapDetails.swap = null
        console.log "bot=",this.props.bot
        return


    render: ()->
        <div id="swap-step-1" className="swap-step">
            <h2>Choose a token to receive</h2>
            <div className="segment-control">
                <div className="line"></div><br/>
                <div className="dot selected"></div>
                <div className="dot"></div>
                <div className="dot"></div>
                <div className="dot"></div>
            </div>
            { if this.props.bot
                <div>
                    <p className="description">{this.props.bot.description} <a className="more-link" href={swapbot.addressUtils.publicBotAddress(this.props.bot.username, this.props.bot.id, window.location)} target="_blank"><i className="fa fa-sign-out"></i></a></p>
                    <ul className="wide-list">
                        { 
                            swaps = this.props.bot.swaps
                            if swaps
                                for swap, offset in swaps
                                    <SwapbotChooseItem key={'swap' + offset} bot={this.props.bot} swap={swap} chooseSwapFn={this.chooseSwapFn} />
                        }
                    </ul>
                </div>
            }
        </div>

