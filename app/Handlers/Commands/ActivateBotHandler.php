<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Swapbot\Commands\ActivateBot;
use Swapbot\Providers\EventLog\Facade\EventLog;
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

        // get a payment address (if needed)
        if (!$bot['payment_address_id']) {
            $payment_address = $this->xchain_client->newPaymentAddress();
            $update_vars['payment_address_id'] = $payment_address['id'];
            $update_vars['address'] = $payment_address['address'];
            EventLog::log('bot.paymentAddressCreated', ['id' => $bot['id'], 'payment_address_id' => $payment_address['id'], 'address' => $payment_address['address']]);

            $address = $payment_address['address'];
        } else {

            $address = $bot['address'];
        }

        // monitor it for receives
        if (!$bot['receive_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($address, Config::get('swapbot.webhook_url'), 'receive', true);
            $update_vars['receive_monitor_id'] = $monitor['id'];
            EventLog::log('bot.receiveMonitorCreated', ['id' => $bot['id'], 'receive_monitor_id' => $monitor['id']]);
        }

        // and monitor for sends
        if (!$bot['send_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($address, Config::get('swapbot.webhook_url'), 'send', true);
            $update_vars['send_monitor_id'] = $monitor['id'];
            EventLog::log('bot.sendMonitorCreated', ['id' => $bot['id'], 'send_monitor_id' => $monitor['id']]);
        }


        // update the bot
        $this->repository->update($bot, $update_vars);

    }

}
