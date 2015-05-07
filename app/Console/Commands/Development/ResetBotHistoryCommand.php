<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ResetBotHistoryCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:reset-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets a bot by deleting all events and swaps';


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
            ['asset'       , 'a',  InputOption::VALUE_OPTIONAL, 'Asset', 'SOUP'],
            ['quantity'    , 'u',  InputOption::VALUE_OPTIONAL, 'Asset Quantity', '1000'],
            ['btc-quantity', 'b',  InputOption::VALUE_OPTIONAL, 'BTC Quantity', '10'],
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
        $bot = $bot_repository->findByID($bot_id);
        if (!$bot) {
            // try uuid
            $bot = $bot_repository->findByUuid($bot_id);
        }
        if (!$bot) { throw new Exception("Unable to find bot", 1); }


        $asset = $this->input->getOption('asset');
        $asset_quantity = $this->input->getOption('quantity');
        $btc_quantity = $this->input->getOption('btc-quantity');

        DB::transaction(function() use ($bot) {
            // clear billed event ID
            $this->comment("clearing transactions billed_event_id");
            $transaction_repository = app('Swapbot\Repositories\TransactionRepository');
            foreach ($transaction_repository->findByBotID($bot['id']) as $transaction) {
                $transaction_repository->update($transaction, ['billed_event_id' => null]);
            }

            // get rid of all bot ledger entries
            $this->comment("deleting bot ledger entries");
            $ledger_repository = app('Swapbot\Repositories\BotLedgerEntryRepository');
            foreach ($ledger_repository->findByBotId($bot['id']) as $bot_ledger_entry) {
                $ledger_repository->delete($bot_ledger_entry);
            }

            // get rid of all bot events
            $this->comment("deleting bot events");
            $event_repository = app('Swapbot\Repositories\BotEventRepository');
            foreach ($event_repository->findByBotId($bot['id']) as $bot_event) {
                $event_repository->delete($bot_event);
            }

            // get rid of all swaps
            $this->comment("deleting swaps");
            $swap_repository = app('Swapbot\Repositories\SwapRepository');
            $customer_repository = app('Swapbot\Repositories\CustomerRepository');
            foreach ($swap_repository->findByBotId($bot['id']) as $swap) {
                // get rid of all customers for this swap
                foreach ($customer_repository->findBySwap($swap) as $customer) {
                    $customer_repository->delete($customer);
                }

                $swap_repository->delete($swap);
            }

            // get rid of all transactions
            $this->comment("deleting transactions");
            $transaction_repository = app('Swapbot\Repositories\TransactionRepository');
            foreach ($transaction_repository->findByBotID($bot['id']) as $transaction) {
                $transaction_repository->delete($transaction);
            }
        });

        // update balances
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $new_balances = [
            'BTC' => $btc_quantity,
        ];
        if ($asset) {
            $new_balances[$asset] = $asset_quantity;
        }

        $this->comment("updating balances to ".json_encode($new_balances, 192));
        $update_vars = ['balances' => $new_balances];
        $bot_repository->update($bot, $update_vars);

        $this->info("done");


    }

}
