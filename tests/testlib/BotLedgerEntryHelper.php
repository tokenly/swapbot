<?php

use Swapbot\Models\Bot;
use Swapbot\Repositories\BotLedgerEntryRepository;

class BotLedgerEntryHelper  {

    function __construct(BotLedgerEntryRepository $bot_ledger_entry_repository) {
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
    }


    public function sampleBotLedgerEntryVars() {
        return [
            'is_credit'    => false,
            'amount'       => 100000000,
            'asset'        => 'BTC',
            'user_id'      => null,
            'bot_id'       => null,
            'bot_event_id' => null,
        ];
    }


    // creates a bot
    //   directly in the repository (no validation)
    public function newSampleBotLedgerEntry($bot=null, $ledger_entry_vars=[]) {
        $attributes = array_replace_recursive($this->sampleBotLedgerEntryVars(), $ledger_entry_vars);
        if ($bot == null) { $bot = app('BotHelper')->newSampleBotWithUniqueSlug(); }

        if (!isset($attributes['bot_id'])) { $attributes['bot_id'] = $bot['id']; }
        if (!isset($attributes['user_id'])) { $attributes['user_id'] = $bot['user_id']; }
        if (!isset($attributes['bot_event_id'])) {
            $bot_event = app('BotEventHelper')->newSampleBotEvent($bot);
            $attributes['bot_event_id'] = $bot_event['id'];
        }


        $bot_ledger_entry_model = $this->bot_ledger_entry_repository->create($attributes);
        return $bot_ledger_entry_model;
    }




}
