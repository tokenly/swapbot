<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LinusU\Bitcoin\AddressValidator;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SweepBotCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:sweep';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sweeps all tokens and value from the bot';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
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
            ['payment' , 'p', InputOption::VALUE_NONE, 'Payment Address Only'],
            ['public' ,  'u', InputOption::VALUE_NONE, 'Public Address Only'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $bot_id = $this->input->getArgument('bot-id');
        $destination = $this->input->getArgument('destination');
        if (!AddressValidator::isValid($destination)) {
            $this->error("Destination $destination is not a valid bitcoin address");
            return;
        }
        $is_dry_run = !!$this->input->getOption('dry-run');
        if ($is_dry_run) { $this->comment("[Dry Run]"); }

        // payment or public
        $do_payment = !!$this->input->getOption('payment');
        $do_public = !!$this->input->getOption('public');
        if (!$do_payment AND !$do_public) {
            $do_payment = true;
            $do_public = true;
        }

        // load the bot
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByID($bot_id);
        if (!$bot) { $bot = $bot_repository->findByUuid($bot_id); }
        if (!$bot) {
            $this->error("Unable to find bot with id $bot_id");
            return;
        }

        // get the bot balances
        $xchain_client = app('Tokenly\XChainClient\Client');
        $this->comment("Loading Bot Balances");

        try {
            if ($do_payment) {
                $payment_address_uuid = $bot['payment_address_id'];
                $payment_balances = $xchain_client->getBalances($bot['payment_address']);
                $this->info("payment address balances: ".json_encode($payment_balances, 192));
            }

            if ($do_public) {
                $public_address_uuid = $bot['public_address_id'];
                $public_balances = $xchain_client->getBalances($bot['address']);
                $this->info("public address balances: ".json_encode($public_balances, 192));
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            if (!$is_dry_run) { return; }
        }

        // do sweeps
        if ($is_dry_run) {
            if ($do_payment) {
                $this->comment("[Dry Run] Would sweep all assets for payment address {$bot['payment_address']}");
            }
            if ($do_public) {
                $this->comment("[Dry Run] Would sweep all assets for public address {$bot['address']}");
            }
        } else {
            if ($do_payment) {
                $this->comment("Sweeping all assets for payment address {$bot['payment_address']}");
                $result = $xchain_client->sweepAllAssets($payment_address_uuid, $destination);
                $this->info("sweep payment address result: ".json_encode($result, 192));
            }

            if ($do_public) {
                $this->comment("Sweeping all assets for public address {$bot['address']}");
                $result = $xchain_client->sweepAllAssets($public_address_uuid, $destination);
                $this->info("sweep public address result: ".json_encode($result, 192));
            }
        }

        // $update_vars = ['state' => BotState::BRAND_NEW, 'balances' => $new_balances];
        // $bot_repository->update($bot, $update_vars);

        $this->info("done");


    }

}
