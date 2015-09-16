# ---- begin references
UserChoiceStore = require '../../stores/UserChoiceStore'
# ---- end references

NeedHelpLink = null


getViewState = ()->
    return { userChoices: UserChoiceStore.getUserChoices() }


# ##############################################################################################################################
# The place order input component

NeedHelpLink = React.createClass
    displayName: 'NeedHelpLink'

    getInitialState: ()->
        {}

    render: ()->
        subject = "Swapbot Help"

        if this.props.botName?
            subject = "Help with #{this.props.botName}"

        return <span>
                <a href="mailto:#{encodeURIComponent('Tokenly Team <team@tokenly.co>')}?subject=#{encodeURIComponent(subject)}" className="shadow-link helpLink" target="_blank">
                    Need Help?
                </a>
            </span>

# #############################################
module.exports = NeedHelpLink
