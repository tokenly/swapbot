<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\ProcessIncomeForwardingForAllBots;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\BotEventLogger;
use Swapbot\Swap\Processor\Util\BalanceUpdater;
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
        foreach ($this->bot_repository->findAll() as $bot) {
            // check balance
            foreach ($bot['income_rules'] as $income_rule_config) {
                if ($bot->getBalance($income_rule_config['asset']) >= $income_rule_config['minThreshold']) {
                    try {
                        // send the transaction
                        $asset = $income_rule_config['asset'];
                        $destination = $income_rule_config['address'];
                        $quantity = $income_rule_config['paymentAmount'];
                        $fee = $bot['return_fee'];
                        $send_result = $this->xchain_client->send($bot['public_address_id'], $destination, $quantity, $asset, $fee);

                        // log the event
                        $this->bot_event_logger->logIncomeForwardingResult($bot, $send_result, $destination, $quantity, $asset);

                        // update the balance
                        $balance_deltas = $this->balance_updater->modifyBalanceDeltasForSend([], $asset, $quantity, $fee);
                        $this->balance_updater->updateBotBalances($bot, $balance_deltas);
                    } catch (Exception $e) {
                        // log failure
                        $this->bot_event_logger->logIncomeForwardingFailed($bot, $e);
                    }
                }
            }
        }


    }

}
