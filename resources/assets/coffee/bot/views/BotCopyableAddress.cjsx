# ---- begin references
ReactZeroClipboard = require '../views/includes/ReactZeroClipboard'
# ---- end references

# pass BotCopyableAddress up
BotCopyableAddress = null


getViewState = ()->
    return {
        addressCopied: false
    }


# ############################################################
BotCopyableAddress = React.createClass
    displayName: 'BotCopyableAddress'

    handleOnClick: (e)->
        e.preventDefault()
        return

    getInitialState: ()->
        return getViewState()

    onAfterCopy: () ->
        this.setState({addressCopied: true})

        if this.copiedTimeoutRef? then clearTimeout(this.copiedTimeoutRef)

        this.copiedTimeoutRef = setTimeout ()=>
            this.setState({addressCopied: false})
            this.copiedTimeoutRef = null
        , 2500

        return

    render: ->
        bot = this.props.bot
        <ReactZeroClipboard 
            text={bot.address}
            onAfterCopy={this.onAfterCopy}
        >
            <a className={"swap-address"+(if this.state.addressCopied then ' copied' else '')} onClick={this.handleOnClick} href="#copy-address">
                <span> {bot.address}</span>
                <span className="copyToClipboard">
                {
                    if this.state.addressCopied
                        <span>Copied</span>
                    else
                        <span></span>
                }
                </span>

            </a>
        </ReactZeroClipboard>

# #############################################
module.exports = BotCopyableAddress

