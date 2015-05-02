# The bot status component updates the green or red indicator
#   and the bot status on the public bot page
BotStatusComponent = React.createClass
    displayName: 'BotStatusComponent'

    # need to use a new BotStreamStore
    getInitialState: ()->
        return {
            botStatus: 'inactive'
        }

    componentDidMount: ()->
        return

    componentWillUnmount: ()->
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

