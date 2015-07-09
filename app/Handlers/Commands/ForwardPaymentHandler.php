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
        $payment_address_uuid = $bot['payment_address_id'];
        $result = $this->xchain_client->send($payment_address_uuid, $destination, $quantity, $asset, $fee, $dust_size=null, $request_id);

        // log it
        $tx_id = $result['txid'];
        $bot_event = $this->bot_event_logger->logPaymentForwarded($bot, $quantity, $asset, $destination, $fee, $tx_id);

        // decrease the balance
        $this->bot_ledger_entry_repository->addDebit($bot, $quantity + $fee, $asset, $bot_event);
    }

}
