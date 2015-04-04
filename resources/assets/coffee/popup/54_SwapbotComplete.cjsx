
SwapbotComplete = React.createClass
    displayName: 'SwapbotComplete'

    componentDidMount: ()->
        return

    componentWillUnmount: ()->
        return


    # ########################################################################
    # matched bot event
    

    # ########################################################################


    getInitialState: ()->
        return {
        }

    render: ()->
        bot = this.props.bot
        swapDetails = this.props.swapDetails
        txInfo = swapDetails.txInfo or {}

        <div id="swap-step-4" className="swap-step">
            <h2>Successfully finished</h2>
            <div className="segment-control">
                <div className="line"></div><br/>
                <div className="dot"></div>
                <div className="dot"></div>
                <div className="dot"></div>
                <div className="dot selected"></div>
            </div>

            <div className="icon-success center"></div>

            <p>Exchanged <b>{txInfo.inQty} {txInfo.inAsset}</b> for <b>{txInfo.outQty} {txInfo.outAsset}</b> with {txInfo.address}.</p>
            <p><a href="#" className="details-link" target="_blank">Transaction details <i className="fa fa-arrow-circle-right"></i></a></p>
        </div>

