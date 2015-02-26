# planutils functions
sbAdmin.planutils = do ()->
    planutils = {}

    planutils.paymentPlanDesc = (planID)->
        return planutils.planData(planID)?.name or 'unknown'

    planutils.planData = (planID)->
        plans = planutils.allPlansData()
        if plans[planID]?
            return plans[planID]

        return null

    planutils.allPlansData = ()->
        initialFuel = 0.005

        return {
            txfee001: {
                id: "txfee001"
                name: "0.005 BTC creation fee + .001 BTC per TX"
                creationFee: 0.005
                txFee: 0.001
                initialFuel: initialFuel
            }
            txfee002: {
                id: "txfee002"
                name: "0.05 BTC creation fee + .0005 BTC per TX"
                creationFee: 0.05
                txFee: 0.0005
                initialFuel: initialFuel
            }
            txfee003: {
                id: "txfee003"
                name: "0.5 BTC creation fee + .0001 BTC per TX"
                creationFee: 0.5
                txFee: 0.0001
                initialFuel: initialFuel
            }
        }


    planutils.allPlanOptions = ()->
        opts = for k, v of planutils.allPlansData()
            {k: v.name, v: v.id}    
        return opts
        

    return planutils
