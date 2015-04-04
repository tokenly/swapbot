swapEventRenderer = do ()->
    exports = {}

    renderers = {}
    renderers['unconfirmed.tx'] = (bot, swap, swapEventRecord)->
        event = swapEventRecord.event
        return <li className="pending">
            <div className="status-icon icon-pending"></div>
            {event.msg}
            <br/>
            <small>Waiting for {swapbot.botUtils.confirmationsProse(bot)} to send {event.outQty} {event.outAsset}</small>
        </li>

    renderers['swap.confirming'] = (bot, swap, swapEventRecord)->
        event = swapEventRecord.event
        return <li className="pending">
            <div className="status-icon icon-pending"></div>
            {event.msg}
            <br/>
            <small>Received {event.confirmations} of {swapbot.botUtils.confirmationsProse(bot)} to send {event.outQty} {event.outAsset}</small>
        </li>

    renderers['swap.failed'] = (bot, swap, swapEventRecord)->
        event = swapEventRecord.event
        return <li className="failed">
            <div className="status-icon icon-failed"></div>
            {event.msg}
            <br/>
            <small>Failed to swap to {event.destination}</small>
        </li>

    renderers['swap.sent'] = (bot, swap, swapEventRecord)->
        event = swapEventRecord.event
        return <li className="confirmed">
            <div className="status-icon icon-confirmed"></div>
            {event.msg}
        </li>

    exports.renderSwapStatus = (bot, swap, swapEventRecord)->
        # console.log "swap=",swap
        if swapEventRecord?
            name = swapEventRecord.event.name
            if renderers[name]?
                return renderers[name](bot, swap, swapEventRecord)

        console.log "renderSwapStatus swap=#{swap.id} swapEventRecord=",swapEventRecord
        return <li className="pending">
                <div className="status-icon icon-pending"></div>
                Processing swap from {swap.address}
                <br />
                <small>Waiting for more information</small>
            </li>



    # #############################################
    return exports



    # <li className="pending">
    #     <div className="status-icon icon-pending"></div>
    #     <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> waiting to exchange <b>0.2BTC</b> for <b>200,000 LTBCOIN</b>.
    #     <br>
    #     <small>Waiting for 1 confirmation to send 0.1 BTC</small>
    # </li>
    #     <li className="failed">
    #         <div className="status-icon icon-failed"></div>
    #         Failed to process <b>100,000 UNKNOWNCOIN</b>.
    #     </li>
    #     <li className="confirmed">
    #         <div className="status-icon icon-confirmed"></div>
    #         <a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> successfully exchanged <b>0.1BTC</b> for <b>100,000</b> LTBCOIN.
    #     </li>
