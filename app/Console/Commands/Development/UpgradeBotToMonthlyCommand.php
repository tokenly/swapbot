<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpgradeBotToMonthlyCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:upgrade-to-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrades a bot to a monthly billing account';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
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
            ['reset'       , 'r',  InputOption::VALUE_NONE, 'Reset Payment History'],
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

        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) { throw new Exception("Unable to find bot", 1); }

        DB::transaction(function() use ($bot) {
            $reset = $this->input->getOption('reset');

            $bot_repository = app('Swapbot\Repositories\BotRepository');
            $ledger_repository = app('Swapbot\Repositories\BotLedgerEntryRepository');
            $transaction_repository = app('Swapbot\Repositories\TransactionRepository');
            $event_logger = app('Swapbot\Swap\Logger\BotEventLogger');
            $xchain_client = app('Tokenly\XChainClient\Client');

            if ($reset) {
                // clear billed event ID
                $this->comment("clearing transactions billed_event_id");
                foreach ($transaction_repository->findByBotID($bot['id']) as $transaction) {
                    $transaction_repository->update($transaction, ['billed_event_id' => null]);
                }

                // get rid of all bot ledger entries
                $this->comment("deleting bot ledger entries");
                foreach ($ledger_repository->findByBotId($bot['id']) as $bot_ledger_entry) {
                    $ledger_repository->delete($bot_ledger_entry);
                }

                // update ledger balance
                $new_balances = $xchain_client->getBalances($bot['payment_address']);
                $this->comment("applying new balances to payment address:\n".json_encode($new_balances, 192));
                foreach($new_balances as $asset => $new_balance) {
                    if ($new_balance > 0) {
                        $bot_event = $event_logger->logManualPayment($bot, $new_balance, $asset);
                        $ledger_repository->addCredit($bot, $new_balance, $asset, $bot_event);
                    }
                }
            }

            $update_vars = ['payment_plan' => 'monthly001'];
            $bot_repository->update($bot, $update_vars);
        });

        $this->info("done");
    }

}
