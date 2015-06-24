BotStatusComponent = null
do ()->

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
            isActive = if lastEvent? then lastEvent.isActive else false
            # console.log "lastEvent",lastEvent
            # console.log "isActive",isActive
            <div>
                {
                    if isActive
                        <div><div className="status-dot bckg-green"></div>Active</div>
                    else
                        <div><div className="status-dot bckg-red"></div>Inactive</div>
                }
            </div>


# <button className="button-question"></button>

