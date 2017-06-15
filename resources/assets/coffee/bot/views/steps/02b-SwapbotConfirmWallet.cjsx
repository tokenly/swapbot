# ---- begin references
UserInputActions = require '../../actions/UserInputActions'
NeedHelpLink = require '../../views/includes/NeedHelpLink'
# ---- end references

SwapbotConfirmWallet = null

getViewState = ()->
    return { 
    }

# ##############################################################################################################################
# Confirm you have a correct wallet

SwapbotConfirmWallet = React.createClass
    displayName: 'SwapbotConfirmWallet'

    getInitialState: ()->
        return $.extend(
            {},
            getViewState()
        )

    _onChange: ()->
        this.setState(getViewState())
        return

    componentDidMount: ()->
        this.listenForKeyboardShortcuts()
        return

    componentWillUnmount: ()->
        this.stopListeningForKeyboardShortcuts()
        return

    listenForKeyboardShortcuts: ()->
        $(document).on 'keydown.confirmwallet', (e)->
            if e.keyCode == 89
                UserInputActions.confirmWallet()
            return
        return

    stopListeningForKeyboardShortcuts: ()->
        $(document).off '.confirmwallet'
        return

    confirmWalletOnClick: (e)->
        e.preventDefault()
        UserInputActions.confirmWallet()


    render: ()->
        bot = this.props.bot

        return <div id="swapbot-container" className="section grid-100">
            <div id="swap-step-2" className="content">
                <h2>Confirm Your Wallet</h2>
                <div className="segment-control">
                    <div className="line"></div>
                    <br />
                    <div className="dot"></div>
                    <div className="dot selected"></div>
                    <div className="dot"></div>
                    <div className="dot"></div>
                </div>

                <div>
                    <p className="description">
                        <span style={fontWeight: 'bold', color: "#e74c3c"}>Important:</span> DO NOT PURCHASE using Coinbase, a currency exchange or other wallets where you do not control the address.  If sending bitcoin, you <strong>MUST</strong> send from a Counterparty compatible wallet.
                    </p>
                    <div style={height: "16px"}></div>

                    <strong>Are you using a Counterparty compatible bitcoin wallet?</strong>
                    <div style={height: "26px"}></div>
                    <a href="#yes" onClick={this.confirmWalletOnClick} className="btn-action bckg-green"><span className="keyboard-shortcut">Y</span>ES</a>
                    <a href="http://pockets.tokenly.com" target="_blank" className="btn-action bckg-red">NO</a>
                    <a href="http://pockets.tokenly.com" target="_blank" className="btn-action bckg-yellow">I DON&rsquo;T KNOW</a>
                    <div style={height: "36px"}></div>

                    <p className="description description-light">
                        <strong>Did You Know?</strong><br />
                        The Tokens you purchase will be sent back to the exact same address you use to send them, so it&rsquo;s very important that you make your purchase from your own wallet.
                    </p>



                </div>

                <div id="GoBackLink">
                    <a id="go-back" onClick={UserInputActions.goBackOnClick} href="#go-back" className="shadow-link">Go Back</a>

                    <NeedHelpLink botName={bot.name} />
                </div>
                
            </div>
        </div>




# #############################################
module.exports = SwapbotConfirmWallet

