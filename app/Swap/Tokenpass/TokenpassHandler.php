<?php

namespace Swapbot\Swap\Tokenpass;

use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\TokenpassAPI;

/**
* TokenpassHandler
*/
class TokenpassHandler
{

    function __construct(TokenpassAPI $tokenpass_api, BotRepository $bot_repository) {
        $this->tokenpass_api  = $tokenpass_api;
        $this->bot_repository = $bot_repository;
    }

    public function botIsRegisteredWithTokenpass(Bot $bot) {
        return $bot['registered_with_tokenpass'];
    }

    public function shouldRegisterBotWithTokenpass(Bot $bot) {
        // check if already registered
        if ($bot['registered_with_tokenpass']) { return false; }

        // check if it is active
        $active = $bot->isActive();
        if ($bot->isShuttingDown()) { $active = false; }

        if ($active) {
            // yes - we should register
            return true;
        }

        // do not register
        return false;
    }

    public function registerBotWithTokenpass(Bot $bot) {
        // register the bot
        try {
            $success = $this->tokenpass_api->registerProvisionalSourceWithProof($bot['address']);
            EventLog::info('tokenpass.registerAsSource', ['botId' => $bot['id']]);

            // mark the bot as registered
            $this->bot_repository->update($bot, [
                'registered_with_tokenpass' => true,
            ]);

            return true;
        } catch (Exception $e) {
            EventLog::logError('tokenpass.registerAsSource.error', $e, ['botId' => $bot['id']]);
            return false;
        }
    }
}

