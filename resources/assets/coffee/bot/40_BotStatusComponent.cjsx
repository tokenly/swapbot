# The bot status component updates the green or red indicator
#   and the bot status on the public bot page
BotStatusComponent = React.createClass
    displayName: 'BotStatusComponent'

    getInitialState: ()->
        return {
        }

    componentDidMount: ()->
        this.props.eventSubscriber.subscribe (botEvent)=>
            newState = swapbot.botUtils.newBotStatusFromEvent(this.state.botStatus, botEvent)
            this.setState({botStatus: newState})
        return

    render: ->
        <div>
            {
                if this.state.botStatus == 'active'
                    <div><div className="status-dot bckg-green"></div>Active</div>
                else
                    <div><div className="status-dot bckg-red"></div>Inactive</div>
            }
            <button className="button-question"></button>
        </div>

