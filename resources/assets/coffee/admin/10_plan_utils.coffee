# planutils functions
sbAdmin.planutils = do ()->
    planutils = {}

    # load plans

    planutils.paymentPlanDesc = (planID, allPlansData)->
        return planutils.planData(planID, allPlansData)?.name or 'unknown plan '+planID

    planutils.planData = (planID, allPlansData)->
        plans = allPlansData
        if plans?[planID]?
            return plans[planID]

        return null

    # planutils.allPlansData = ()->
    #     return {
    #         monthly001: {
    #             id: "monthly001"
    #             name: "Monthly SwapBot Rental.  $7 in BTC / 60,000 LTBCOIN / 1 TOKENLY"
    #             type: "monthly"
    #         }
    #     }


    planutils.allPlanOptions = (allPlansData)->
        opts = []
        for k, v of allPlansData
            description = ''
            if v.type == 'monthly'
                description += ' / '
                first = true
                for mrk, mrv of v.monthlyRates
                    description += if first then '' else ', '
                    description += mrv.description
                    first = false
                
            opts.push({k: v.name+description, v: v.id})
        if opts.length == 0
            opts = [{k: '- No Plans Available -', v: ''}]

        return opts
        

    return planutils
