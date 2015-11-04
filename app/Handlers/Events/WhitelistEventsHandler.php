<?php

namespace Swapbot\Handlers\Events;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Events\Event;
use Swapbot\Events\WhitelistWasDeleted;
use Swapbot\Repositories\BotRepository;

class WhitelistEventsHandler {


    public function __construct(BotRepository $bot_repository) {
        $this->bot_repository = $bot_repository;
    }

    public function whitelistWasDeleted(WhitelistWasDeleted $event) {
        $whitelist = $event->whitelist;

        // find each bot that subscribes to this whitelist and update it
        $bots_to_update = $this->bot_repository->findAllByWhitelistUuid($whitelist['uuid']);
        foreach($bots_to_update as $bot_to_update) {
            $this->bot_repository->update($bot_to_update, ['whitelist_uuid' => null]);
        }

        return;
    }

    /// ------------------------------------------------------------------------------------------------------------

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events) {
        $events->listen('Swapbot\Events\WhitelistWasDeleted', 'Swapbot\Handlers\Events\WhitelistEventsHandler@whitelistWasDeleted');
    }



}
