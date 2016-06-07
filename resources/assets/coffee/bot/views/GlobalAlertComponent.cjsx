# ---- begin references
UserInterfaceStateStore = require '../stores/UserInterfaceStateStore'
marked = require '../../../../../node_modules/marked'
# ---- end references

GlobalAlertComponent = null

getViewState = ()->
    return {
        globalAlert: UserInterfaceStateStore.getUIState().globalAlert
    }


# ############################################################################################################
# The bot status component updates the green or red indicator
#   and the bot status on the public bot page

GlobalAlertComponent = React.createClass
    displayName: 'GlobalAlertComponent'

    # need to use a new BotStreamStore
    getInitialState: ()->
        return getViewState()

    _onChange: ()->
        this.setState(getViewState())
        return

    componentDidMount: ()->
        UserInterfaceStateStore.addChangeListener(this._onChange)
        return

    componentWillUnmount: ()->
        UserInterfaceStateStore.removeChangeListener(this._onChange)
        return

    render: ->
        globalAlert = this.state.globalAlert
        isActive = globalAlert.status
        
        if isActive
            html = marked(globalAlert.content or '')
            return <div>
                <div class="clearfix"></div>
                <div id="GlobalAlert" className="section grid-100">
                    <div className="header">Attention</div>
                    <div className="content" dangerouslySetInnerHTML={{__html: html}} />
                </div>
            </div>
        return null


# #############################################
module.exports = GlobalAlertComponent

