# swapEventRenderer

# <ul className="swap-list">
#     <li className="pending">
#         <div className="status-icon icon-pending"></div>
#         <div className="status-content">
#             <span><a target="_blank" href="http://blockchain.info/address/hello">1MyPers...Ce6f7cD</a> waiting to exchange <b>0.2BTC</b> for <b>200,000 LTBCOIN</b>.<br>
#                 <small>Waiting for 1 confirmation to send 0.1 BTC</small></span>
#         </div>
#     </li>
# </ul>


swapEventRenderer = do ()->
    exports = {}

    renderers = {}
    renderers['unconfirmed.tx'] = (bot, swap, swapEventRecord, fromNow)->
        event = swapEventRecord.event
        return <li className="pending">
            <div className="status-icon icon-pending"></div>
            <div className="status-content">
                <span>
                <div className="date">{fromNow}</div>
                {event.msg}
                <br/>
                <small>Waiting for {swapbot.botUtils.confirmationsProse(bot)} to send {event.outQty} {event.outAsset}</small>
                </span>
            </div>
        </li>

    renderers['swap.confirming'] = (bot, swap, swapEventRecord, fromNow)->
        event = swapEventRecord.event
        return <li className="pending">
            <div className="status-icon icon-pending"></div>
            <div className="status-content">
                <span>
                {event.msg}
                <br/>
                <small>Received {event.confirmations} of {swapbot.botUtils.confirmationsProse(bot)} to send {event.outQty} {event.outAsset}</small>
                </span>
            </div>
        </li>

    renderers['swap.failed'] = (bot, swap, swapEventRecord, fromNow)->
        event = swapEventRecord.event
        return <li className="failed">
            <div className="status-icon icon-failed"></div>
            <div className="status-content">
                <span>
                {event.msg}
                <br/>
                <small>Failed to swap to {event.destination}</small>
                </span>
            </div>
        </li>

    renderers['swap.sent'] = (bot, swap, swapEventRecord, fromNow)->
        event = swapEventRecord.event
        return <li className="confirmed">
            <div className="status-icon icon-confirmed"></div>
            <div className="status-content">
                <span>
                    {event.msg}
                    <a href={"/public/#{bot.username}/swap/#{swap.id}"} className="details-link" target="_blank"><i className="fa fa-arrow-circle-right"></i></a>
                </span>
            </div>
        </li>

    exports.renderSwapStatus = (bot, swap, swapEventRecord, fromNow)->
        # console.log "swap=",swap
        if swapEventRecord?
            name = swapEventRecord.event.name
            if renderers[name]?
                return renderers[name](bot, swap, swapEventRecord, fromNow)

        # console.log "renderSwapStatus swap=#{swap.id} swapEventRecord=",swapEventRecord
        return <li className="pending">
                <div className="status-icon icon-pending"></div>
                <div className="status-content">
                    <span>
                    Processing swap from {swap.address}
                    <br />
                    <small>Waiting for more information</small>
                    </span>
                </div>
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
