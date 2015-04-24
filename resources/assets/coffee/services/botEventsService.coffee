# swapUtils functions
swapbot = {} if not swapbot?

swapbot.botEventsService = do ()->
    exports = {}



    # #############################################
    # local

    loadBotEvents = (bot, onBotEventData)->
        botId = bot.id
        $.get "/api/v1/public/botevents/#{botId}", (data)=>
            # sort by oldest to newest
            data.sort (a,b)->
                return a.serial - b.serial

            for botEvent in data
                onBotEventData(botEvent)
            return
        return

    subscribeToPusher = (bot, onBotEventData)->
        return swapbot.pusher.subscribeToPusherChanel "swapbot_events_#{bot.id}", (botEvent)->
            onBotEventData(botEvent)




    # #############################################
    # exports

    exports.buildEventSubscriberForBot = (bot)->
        loaded = false
        pusherClient = null
        allEvents = {}
        clients = {}
        nextClientId = 1


        subscriberExports = {}
        subscriberExports.subscribe = (clientOnBotEventData)->

            pushAllEventsToClient = (clientFn)->
                for k, v of allEvents
                    clientFn(v)
                return

            localOnBotEventData = (botEvent)->
                return if allEvents[botEvent.serial]?

                allEvents[botEvent.serial] = botEvent

                for clientId, clientFn of clients
                    clientFn(botEvent)

                return


            newClientId = nextClientId++
            clients[newClientId] = clientOnBotEventData

            if not loaded
                loadBotEvents(bot, localOnBotEventData)
                pusherClient = subscribeToPusher(bot, localOnBotEventData)
                loaded = true

            pushAllEventsToClient(clientOnBotEventData)

            return newClientId

        subscriberExports.unsubscribe = (oldClientId)->
            delete clients[oldClientId] if clients[oldClientId]?
            return

        return subscriberExports

    return exports

