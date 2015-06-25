<?php

namespace Swapbot\Statemachines\BotCommand;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Metabor\Statemachine\Command;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BotLeaseEntryRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\XChainClient\Client;


/*
* BotCommand
*/
class BotCommand extends Command {

    use DispatchesCommands;

    public function updateBotState(Bot $bot, $new_state) {
        $this->getBotRepository()->update($bot, ['state' => $new_state]);

        // log the bot event
        $this->getBotEventLogger()->logBotStateChange($bot, $new_state);
    }

    public function getBotEventLogger() {
        if (!isset($this->bot_event_logger)) { $this->bot_event_logger = app('Swapbot\Swap\Logger\BotEventLogger'); }
        return $this->bot_event_logger;
    }

    public function getBotRepository() {
        if (!isset($this->bot_repository)) { $this->bot_repository = app('Swapbot\Repositories\BotRepository'); }
        return $this->bot_repository;
    }

    public function getBotLedgerEntryRepository() {
        if (!isset($this->bot_ledger_entry_repository)) { $this->bot_ledger_entry_repository = app('Swapbot\Repositories\BotLedgerEntryRepository'); }
        return $this->bot_ledger_entry_repository;
    }

    public function getXChainClient() {
        if (!isset($this->xchain_client)) { $this->xchain_client = app('Tokenly\XChainClient\Client'); }
        return $this->xchain_client;
    }

    public function getBotLeaseEntryRepository() {
        if (!isset($this->bot_lease_entry_repository)) { $this->bot_lease_entry_repository = app('Swapbot\Repositories\BotLeaseEntryRepository'); }
        return $this->bot_lease_entry_repository;
    }


}
