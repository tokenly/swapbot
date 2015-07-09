<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LinusU\Bitcoin\AddressValidator;
use Swapbot\Commands\ForwardPayment;
use Swapbot\Handlers\Commands\ForwardPaymentHandler;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ForwardPaymentCommand extends Command {

    use ConfirmableTrait;
    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:forward-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forwards payment from the bot';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
            ['bot-id',      InputArgument::REQUIRED, 'Bot ID'],
            ['quantity',    InputArgument::REQUIRED, 'Quantity to Send'],
            ['asset',       InputArgument::REQUIRED, 'Asset to Send'],
            ['destination', InputArgument::REQUIRED, 'Destination Address'],
        ];
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {

        return [
            ['dry-run' , 'd', InputOption::VALUE_NONE, 'Dry Run'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        $is_dry_run = !!$this->input->getOption('dry-run');
        if ($is_dry_run) { $this->comment("[Dry Run]"); }

        if (!$is_dry_run) {
            // require confirmation
            if (!$this->confirmToProceed()) { return; }
        }

        $bot_id = $this->input->getArgument('bot-id');
        $asset = $this->input->getArgument('asset');
        $quantity = $this->input->getArgument('quantity');
        $destination = $this->input->getArgument('destination');
        if (!AddressValidator::isValid($destination)) { throw new Exception("Destination $destination is not a valid bitcoin address", 1); }


        // load the bot
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) { throw new Exception("Unable to find bot with id $bot_id", 1); }

        // get the bot balances
        $xchain_client = app('Tokenly\XChainClient\Client');

        try {
            $this->comment("Loading Bot Balances");
            $payment_address_uuid = $bot['payment_address_id'];
            $payment_balances = $xchain_client->getBalances($bot['payment_address']);
            // $this->info("payment address balances: ".json_encode($payment_balances, 192));

            if (!isset($payment_balances[$asset]) OR $payment_balances[$asset] < $quantity) {
                $actual_quantity = isset($payment_balances[$asset]) ? $payment_balances[$asset] : 0;
                throw new Exception("This address had $actual_quantity $asset.  This is not enough to send $quantity $asset", 1);
            }
            if ($asset == 'BTC') {
                $btc_quantity = $quantity + Config::get('swapbot.defaultFee');
            } else {
                $btc_quantity = ForwardPaymentHandler::DEFAULT_REGULAR_DUST_SIZE + Config::get('swapbot.defaultFee');
            }

            if (!isset($payment_balances['BTC']) OR $payment_balances['BTC'] < $btc_quantity) {
                $actual_quantity = isset($payment_balances['BTC']) ? $payment_balances['BTC'] : 0;
                throw new Exception("This address had $actual_quantity BTC.  This is not enough to send $btc_quantity BTC", 1);
            }

            // do payment forwarding
            if ($is_dry_run) {
                $this->comment("[Dry Run] Would send $quantity $asset from {$bot['payment_address']} to $destination");
            } else {
                $this->dispatch(new ForwardPayment($bot, $quantity, $asset, $destination));
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }
        $this->info("done");
    }

}
