<?php namespace Swapbot\Handlers\Commands;

use InvalidArgumentException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use Swapbot\Commands\ActivateBot;
use Swapbot\Repositories\BotRepository;
use Tokenly\XChainClient\Client;

class ActivateBotHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, BotRepository $repository)
    {
        $this->xchain_client = $xchain_client;
        $this->repository  = $repository;

    }

    /**
     * Handle the command.
     *
     * @param  ActivateBot  $command
     * @return void
     */
    public function handle(ActivateBot $command)
    {
        $bot = $command->bot;

        // if this bot is already active, throw an error
        if ($bot['active']) { throw new InvalidArgumentException("Could not activate this bot because this bot is already active."); }


        $update_vars = [];
        // update the bot to active
        $update_vars['active'] = true;

        // get a payment address
        $payment_address = $this->xchain_client->newPaymentAddress();
        $update_vars['payment_address_id'] = $payment_address['id'];
        $update_vars['address'] = $payment_address['address'];

        // and monitor it
        $monitor = $this->xchain_client->newAddressMonitor($payment_address['address'], Config::get('swapbot.webhook_url'), 'receive', true);
        $update_vars['monitor_id'] = $monitor['id'];

        // update the bot
        $this->repository->update($bot, $update_vars);

    }

}
