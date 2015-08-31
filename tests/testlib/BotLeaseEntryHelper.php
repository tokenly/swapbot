<?php

use Swapbot\Models\Bot;
use Swapbot\Repositories\BotLeaseEntryRepository;

class BotLeaseEntryHelper  {

    function __construct(BotLeaseEntryRepository $bot_lease_entry_repository) {
        $this->bot_lease_entry_repository = $bot_lease_entry_repository;
    }


    public function sampleBotLeaseEntryVars() {
        return [
            'start_date'   => Carbon\Carbon::now(),
            'end_date'     => Carbon\Carbon::now()->addMonthNoOverflow(1),
            'user_id'      => null,
            'bot_id'       => null,
            'bot_event_id' => null,
        ];
    }


    // creates a bot
    //   directly in the repository (no validation)
    public function newSampleBotLeaseEntry($bot=null, $lease_entry_vars=[]) {
        $attributes = array_replace_recursive($this->sampleBotLeaseEntryVars(), $lease_entry_vars);
        if ($bot == null) { $bot = app('BotHelper')->newSampleBot(); }

        if (!isset($attributes['bot_id'])) { $attributes['bot_id'] = $bot['id']; }
        if (!isset($attributes['user_id'])) { $attributes['user_id'] = $bot['user_id']; }
        if (!isset($attributes['bot_event_id'])) {
            $bot_event = app('BotEventHelper')->newSampleBotEvent($bot);
            $attributes['bot_event_id'] = $bot_event['id'];
        }


        $bot_lease_entry_model = $this->bot_lease_entry_repository->create($attributes);
        return $bot_lease_entry_model;
    }


}
