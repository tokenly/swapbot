<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessIncomeForwardingForAllBots;
use Swapbot\Handlers\Commands\ForwardPaymentHandler;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
use Swapbot\Swap\Util\RequestIDGenerator;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;

class ProcessIncomeForwardingForAllBotsHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $bot_repository, Client $xchain_client, BalanceUpdater $balance_updater, BotEventLogger $bot_event_logger)
    {
        $this->bot_repository   = $bot_repository;
        $this->xchain_client    = $xchain_client;
        $this->balance_updater  = $balance_updater;
        $this->bot_event_logger = $bot_event_logger;
    }

    /**
     * Handle the command.
     *
     * @param  ProcessIncomeForwardingForAllBots  $command
     * @return void
     */
    public function handle(ProcessIncomeForwardingForAllBots $command)
    {
        $bots_forwarded = [];

        foreach ($this->bot_repository->findAll() as $bot) {
            $was_forwarded = $this->bot_repository->executeWithLockedBot($bot, function($bot) {
                // do not attempt to forward income for a bot that is shutting down
                if ($bot->isShuttingDown()) {
                    return false;
                }

                $was_forwarded = false;

                // check balance
                foreach ($bot['income_rules'] as $income_rule_config) {
                    $bot_balance = $bot->getBalance($income_rule_config['asset']);
                    if ($bot_balance >= $income_rule_config['minThreshold']) {
                        try {
                            $should_send = true;
                            $failure_cache_key = $bot['uuid'].','.$income_rule_config['asset'].',faildata';

                            // send the transaction
                            $asset = $income_rule_config['asset'];
                            $destination = $income_rule_config['address'];
                            $fee = $bot['return_fee'];
                            $quantity = $this->buildQuantityToForward($bot_balance, $income_rule_config, $fee);
                            if ($quantity < ForwardPaymentHandler::DEFAULT_REGULAR_DUST_SIZE) {
                                $err_msg = "Unable to forward income because the amount was too small.";
                                EventLog::logError('income.forward.insufficient', $err_msg, ['id' => $bot['id'], 'quantity' => $quantity, 'asset' => $asset, ]);
                                $should_send = false;
                            }

                            if ($asset != 'BTC') {
                                // check that BTC balance is high enough to send
                                $minimum_btc_balance_to_send = ForwardPaymentHandler::DEFAULT_REGULAR_DUST_SIZE + $fee;
                                $btc_balance = $bot->getBalance('BTC');
                                if ($btc_balance < $minimum_btc_balance_to_send) {
                                    $err_msg = "Unable to forward income because there was not enough BTC fuel.";
                                    EventLog::logError('income.forward.insufficientFuel', $err_msg, ['id' => $bot['id'], 'quantity' => $quantity, 'asset' => $asset, 'btcBalance' => $btc_balance]);
                                    $should_send = false;
                                }
                            }

                            // check failure cache
                            $cached_failure_data = Cache::get($failure_cache_key);
                            if ($cached_failure_data) {
                                $time_until_next_attempt = $cached_failure_data['ttl'] - time();
                                if ($time_until_next_attempt > 0) {
                                    Cache::put($failure_cache_key, $cached_failure_data, 180);
                                    $should_send = false;
                                    EventLog::info('income.forward.delayFailure', ['id' => $bot['id'], 'quantity' => $quantity, 'asset' => $asset, 'attempts' => $cached_failure_data['attempts'], 'remaining' => $time_until_next_attempt]);
                                }
                            }

                            if ($should_send) {
                                // don't do the same income forwarding send within 60 minutes
                                $cache_key = $bot['uuid'].','.$income_rule_config['asset'];
                                $send_uuid = Cache::get($cache_key);
                                if (!$send_uuid) {
                                    $send_uuid = DateProvider::microtimeNow();
                                    Cache::put($cache_key, $send_uuid, 60);
                                }

                                $request_id = RequestIDGenerator::generateSendHash('incomeforward'.','.$bot['uuid'].','.$send_uuid, $destination, $quantity, $asset);
                                EventLog::log('bot.income.process', array_merge(['name' => $bot['name'], 'id' => $bot['id']], compact('destination', 'quantity', 'asset')));
                                $send_result = $this->xchain_client->sendConfirmed($bot['public_address_id'], $destination, $quantity, $asset, $fee, null, $request_id);

                                // log the event
                                $this->bot_event_logger->logIncomeForwardingResult($bot, $send_result, $destination, $quantity, $asset);

                                // clear the send cache
                                Cache::forget($cache_key);

                                // update the balance later
                                $was_forwarded = true;
                            }
                        } catch (Exception $e) {
                            // log failure
                            $this->bot_event_logger->logIncomeForwardingFailed($bot, $e);
                            EventLog::logError('income.forward.failed', $e, ['id' => $bot['id']]);

                            $cached_failure_data = Cache::get($failure_cache_key);
                            if (!$cached_failure_data) {
                                $cached_failure_data = [
                                    'attempts' => 0,
                                ];
                            }
                            ++$cached_failure_data['attempts'];
                            $backoff_count = $cached_failure_data['attempts'] + ($cached_failure_data['attempts']-1 * 2); // 1, 3, 5, etc.
                            $delay = $backoff_count * 600; // 10 minutes
                            $delay = min($delay, 240); // max 4 hours
                            $cached_failure_data['ttl'] = time() + $delay;
                            Cache::put($failure_cache_key, $cached_failure_data, 180);
                        }
                    }
                }

                return $was_forwarded;
            });

            if ($was_forwarded) { $bots_forwarded[] = $bot; }
        }

        // sync balances of all the bots that had income forwarded
        foreach($bots_forwarded as $bot) {
            $this->balance_updater->syncBalances($bot);
        }


    }

    protected function buildQuantityToForward($bot_balance, $income_rule_config, $fee) {
        // send as much as we can to get below the threshold
        $threshold  = $income_rule_config['minThreshold'];
        $chunk_size = $income_rule_config['paymentAmount'];
        $asset      = $income_rule_config['asset'];

        // if the bot balance is exactly equal to the threshold
        //   then forward $chunk_size
        if ($bot_balance == $threshold) {
            $quantity = $chunk_size;
        }

        if ($bot_balance != $threshold) {
            // do as many chunks as possible at once
            $number_of_chunks = ceil(($bot_balance - $threshold) / $chunk_size);
            $quantity = $number_of_chunks * $chunk_size;
        }

        // never send more than we have, even if it was configured that way
        if ($quantity > $bot_balance) { $quantity = $bot_balance; }

        // When sending BTC, always account for the fee
        if ($asset == 'BTC') {
            if ($quantity + $fee > $bot_balance) {
                $quantity = $bot_balance - $fee;
                if ($quantity < 0) { $quantity = 0; }
            }
        }

        return $quantity;
    }

}
