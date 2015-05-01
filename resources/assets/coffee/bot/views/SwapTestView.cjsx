# pass SwapTestView up
SwapTestView = null
do ()->

    getViewState = ()->
        return {
            swaps: SwapsStore.getSwaps()
        }


    # ############################################################
    SwapTestView = React.createClass
        displayName: 'SwapTestView'

        getInitialState: ()->
            return getViewState()

        _onChange: ()->
            this.setState(getViewState())

        componentDidMount: ()->
            SwapsStore.addChangeListener(this._onChange)
            return

        componentWillUnmount: ()->
            SwapsStore.removeChangeListener(this._onChange)
            return

        render: ->
            # console.log "this.state.step=#{this.state.step}"
            <div>
                <h2>All Swaps</h2>
                <ul>
                {
                    for swap in this.state.swaps
                        <li key={swap.id}>{swap.address} ({swap.id})</li>
                }
                </ul>
            </div>

    # ############################################################
    return

