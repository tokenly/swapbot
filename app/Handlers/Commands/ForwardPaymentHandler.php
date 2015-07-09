<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use LinusU\Bitcoin\AddressValidator;
use Swapbot\Commands\ForwardPayment;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\XChainClient\Client;

class ForwardPaymentHandler
{

    const DEFAULT_REGULAR_DUST_SIZE = 0.00005430;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, BotLedgerEntryRepository $bot_ledger_entry_repository, BotEventLogger $bot_event_logger)
    {
        $this->xchain_client               = $xchain_client;
        $this->bot_event_logger            = $bot_event_logger;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
    }

    /**
     * Handle the command.
     *
     * @param  ForwardPayment  $command
     * @return void
     */
    public function handle(ForwardPayment $command)
    {
        $bot         = $command->bot;
        $destination = $command->destination;
        $quantity    = $command->quantity;
        $asset       = $command->asset;
        $request_id  = $command->request_id;

        // send
        $fee = Config::get('swapbot.defaultFee');
        $dust_size = 
        $payment_address_uuid = $bot['payment_address_id'];
        $result = $this->xchain_client->send($payment_address_uuid, $destination, $quantity, $asset, $fee, $dust_size=null, $request_id);

        // log it
        $tx_id = $result['txid'];
        $bot_event = $this->bot_event_logger->logPaymentForwarded($bot, $quantity, $asset, $destination, $fee, $tx_id);

        // decrease the balance
        if ($asset == 'BTC') {
            // send BTC
            $balance = $quantity + $fee;
            $this->bot_ledger_entry_repository->addDebit($bot, $balance, $asset, $bot_event);
        } else {
            // send asset
            $balance = $quantity;
            $this->bot_ledger_entry_repository->addDebit($bot, $balance, $asset, $bot_event);

            // fees and dust BTC
            $balance = $fee + self::DEFAULT_REGULAR_DUST_SIZE;
            $this->bot_ledger_entry_repository->addDebit($bot, $balance, 'BTC', $bot_event);

        }
    }

}
