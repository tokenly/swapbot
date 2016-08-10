<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Events\Event;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Repositories\BotIndexRepository;
use Swapbot\Repositories\SwapIndexRepository;


class BotIndexHandler {

    function __construct(BotIndexRepository $bot_index_repository, SwapIndexRepository $swap_index_repository) {
        $this->bot_index_repository = $bot_index_repository;
        $this->swap_index_repository = $swap_index_repository;
    }



    public function botUpdatedOrCreated(Event $event) {
        $bot = $event->bot;

        DB::transaction(function() use ($bot) {
            // add bot info
            $this->bot_index_repository->clearIndex($bot);
            $this->bot_index_repository->addMultipleValuesToIndex($bot, [
                BotIndexRepository::FIELD_NAME        => trim(strip_tags($bot['name'])),
                BotIndexRepository::FIELD_DESCRIPTION => trim(strip_tags($bot['description_html'])),
                BotIndexRepository::FIELD_USERNAME    => $bot['username'],
            ]);

            // add swaps
            $bot_is_active = $bot->isActive();
            $bot_has_whitelist = $bot->hasWhitelist();
            $this->swap_index_repository->clearIndex($bot);
            $swap_index_rows = [];
            foreach ($bot['swaps'] as $swap_offset => $swap_config) {
                foreach ($swap_config->buildIndexEntries() as $swap_index_row) {
                    if ($swap_index_row) {
                        $swap_index_row['swap_offset'] = $swap_offset;
                        $swap_index_row['active']      = $bot_is_active;
                        $swap_index_row['whitelisted'] = $bot_has_whitelist;

                        $swap_index_rows[] = $swap_index_row;
                    }
                }
            }
            if ($swap_index_rows) {
                $this->swap_index_repository->addValues($bot, $swap_index_rows);
            }

        });
    }

    public function botDeleted(Event $event) {
        $bot = $event->bot;
        $this->bot_index_repository->clearIndex($bot);
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('Swapbot\Events\BotCreated', 'Swapbot\Handlers\Events\BotIndexHandler@botUpdatedOrCreated');
        $events->listen('Swapbot\Events\BotUpdated', 'Swapbot\Handlers\Events\BotIndexHandler@botUpdatedOrCreated');
        $events->listen('Swapbot\Events\BotDeleted', 'Swapbot\Handlers\Events\BotIndexHandler@botDeleted');
    }


}
