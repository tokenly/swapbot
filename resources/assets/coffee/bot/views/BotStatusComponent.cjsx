# ---- begin references
BotstreamStore = require '../stores/BotstreamStore'
# ---- end references

BotStatusComponent = null

getViewState = ()->
    return {
        lastEvent: BotstreamStore.getLastEvent()
    }


# ############################################################################################################
# The bot status component updates the green or red indicator
#   and the bot status on the public bot page

BotStatusComponent = React.createClass
    displayName: 'BotStatusComponent'

    # need to use a new BotStreamStore
    getInitialState: ()->
        return getViewState()

    _onChange: ()->
        this.setState(getViewState())
        return

    componentDidMount: ()->
        BotstreamStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        BotstreamStore.removeChangeListener(this._onChange)
        return

    render: ->
        lastEvent = this.state.lastEvent
        isActive = false
        inActiveText = "Inactive"
        if lastEvent?
            isActive = lastEvent.isActive
            if this.props.bot.state == 'shuttingDown'
                isActive = false
                inActiveText = "Shutting Down"

        <div>
            {
                if isActive
                    <div><div className="status-dot bckg-green"></div>Active</div>
                else
                    <div><div className="status-dot bckg-red"></div>{inActiveText}</div>
            }
        </div>


# #############################################
module.exports = BotStatusComponent

