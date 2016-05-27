<?php

namespace Swapbot\Swap\Tokenpass;

use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\BotRepository;
use Tokenly\CurrencyLib\Quantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\TokenpassAPI;

/**
* TokenpassHandler
*/
class TokenpassHandler
{

    const PROMISE_TIMEOUT_SECONDS = 7200; // 2 hours

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

    public function updateOrCreateTokenPromise(Bot $bot, $promise_id, $destination, Quantity $quantity, $asset, $txid=null) {
        Log::debug("updateOrCreateTokenPromise \$promise_id=".json_encode($promise_id, 192));
        if ($promise_id === null) {
            return $this->createNewTokenPromise($bot, $destination, $quantity, $asset, $txid);
        }

        $source = $bot['address'];
        $expiration = time() + self::PROMISE_TIMEOUT_SECONDS;

        // update an existing token promise
        try {
            $update_vars = [
                'expiration' => $expiration,
            ];
            if ($txid !== null) { $update_vars['txid'] = $txid; }
            
            $result = $this->tokenpass_api->updatePromisedTransaction($promise_id, $update_vars);
            $updated_promise_id = $result['tx']['promise_id'];
            EventLog::info('tokenpass.promiseUpdated', ['source' => $source, 'destination' => $destination, 'asset' => $asset, 'quantity' => $quantity->getFloat(), 'promise_id' => $updated_promise_id, 'botId' => $bot['id']]);
            return $updated_promise_id;

        } catch (Exception $e) {
            EventLog::logError('tokenpass.promiseUpdate.error', $e, ['promise_id' => $promise_id, 'botId' => $bot['id']]);
            return null;
        }

    }

    public function createNewTokenPromise(Bot $bot, $destination, Quantity $quantity, $asset, $txid=null) {
        $source = $bot['address'];
        $expiration = time() + self::PROMISE_TIMEOUT_SECONDS;

        // create a new token promise
        try {
            $result = $this->tokenpass_api->promiseTransaction($source, $destination, $asset, $quantity->getSatoshis(), $expiration, $txid);
            $new_promise_id = $result['tx']['promise_id'];
            EventLog::info('tokenpass.promiseCreated', ['source' => $source, 'destination' => $destination, 'asset' => $asset, 'quantity' => $quantity->getFloat(), 'new_promise_id' => $new_promise_id, 'botId' => $bot['id']]);
            return $new_promise_id;
        } catch (Exception $e) {
            EventLog::logError('tokenpass.promiseCreate.error', $e, ['botId' => $bot['id']]);
            return null;
        }

    }

    public function deleteTokenPromise(Bot $bot, $promise_id) {
        try {
            $result = $this->tokenpass_api->deletePromisedTransaction($promise_id);
            EventLog::info('tokenpass.promiseDeleted', ['promise_id' => $promise_id, 'botId' => $bot['id']]);
            return;
        } catch (Exception $e) {
            EventLog::logError('tokenpass.promiseDelete.error', $e, ['promise_id' => $promise_id, 'botId' => $bot['id']]);
            return;
        }

    }


}

