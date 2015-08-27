<?php

namespace Swapbot\Statemachines\BotPaymentCommand;

use Exception;
use Metabor\Statemachine\Command;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\BotEventLogger;


/*
* BotPaymentCommand
*/
class BotPaymentCommand extends Command {


    public function updateBotPaymentState(Bot $bot, $new_payment_state) {
        $this->getBotRepository()->update($bot, ['payment_state' => $new_payment_state]);

        // log the bot event
        $this->getBotEventLogger()->logBotPaymentStateChange($bot, $new_payment_state);
    }

    public function getBotEventLogger() {
        if (!isset($this->bot_event_logger)) { $this->bot_event_logger = app('Swapbot\Swap\Logger\BotEventLogger'); }
        return $this->bot_event_logger;
    }

    public function getBotRepository() {
        if (!isset($this->bot_repository)) { $this->bot_repository = app('Swapbot\Repositories\BotRepository'); }
        return $this->bot_repository;
    }

}
