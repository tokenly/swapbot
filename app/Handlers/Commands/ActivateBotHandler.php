<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Swapbot\Commands\ActivateBot;
use Tokenly\LaravelEventLog\Facade\EventLog;
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

        // get a public address (if needed)
        if (!$bot['public_address_id']) {
            $public_address = $this->xchain_client->newPaymentAddress();
            $update_vars['public_address_id'] = $public_address['id'];
            $update_vars['address'] = $public_address['address'];
            EventLog::log('bot.publicAddressCreated', ['id' => $bot['id'], 'public_address_id' => $public_address['id'], 'address' => $public_address['address']]);

            $public_address = $public_address['address'];
        } else {

            $public_address = $bot['address'];
        }

        // monitor public address for receives
        if (!$bot['public_receive_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($public_address, Config::get('swapbot.webhook_url'), 'receive', true);
            $update_vars['public_receive_monitor_id'] = $monitor['id'];
            EventLog::log('bot.receiveMonitorCreated', ['id' => $bot['id'], 'public_receive_monitor_id' => $monitor['id']]);
        }

        // monitor public address for sends
        if (!$bot['public_send_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($public_address, Config::get('swapbot.webhook_url'), 'send', true);
            $update_vars['public_send_monitor_id'] = $monitor['id'];
            EventLog::log('bot.sendMonitorCreated', ['id' => $bot['id'], 'public_send_monitor_id' => $monitor['id']]);
        }



        // get a payment address (if needed)
        if (!$bot['payment_address_id']) {
            $payment_address = $this->xchain_client->newPaymentAddress();
            $update_vars['payment_address_id'] = $payment_address['id'];
            $update_vars['payment_address'] = $payment_address['address'];
            EventLog::log('bot.paymentAddressCreated', ['id' => $bot['id'], 'payment_address_id' => $payment_address['id'], 'address' => $payment_address['address']]);

            $payment_address = $payment_address['address'];
        } else {

            $payment_address = $bot['address'];
        }

        // monitor public address for receives
        if (!$bot['payment_receive_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($payment_address, Config::get('swapbot.webhook_url'), 'receive', true);
            $update_vars['payment_receive_monitor_id'] = $monitor['id'];
            EventLog::log('bot.paymentReceiveMonitorCreated', ['id' => $bot['id'], 'payment_receive_monitor_id' => $monitor['id']]);
        }

        // monitor public address for sends
        if (!$bot['payment_send_monitor_id']) {
            $monitor = $this->xchain_client->newAddressMonitor($payment_address, Config::get('swapbot.webhook_url'), 'send', true);
            $update_vars['payment_send_monitor_id'] = $monitor['id'];
            EventLog::log('bot.paymentSendMonitorCreated', ['id' => $bot['id'], 'payment_send_monitor_id' => $monitor['id']]);
        }



        // update the bot
        $this->repository->update($bot, $update_vars);

    }

}
