<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Support\Facades\DB;
use Swapbot\Commands\DeleteBot;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\CustomerRepository;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Repositories\TransactionRepository;

class DeleteBotHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(BotRepository $repository, TransactionRepository $transaction_repository, SwapRepository $swap_repository, CustomerRepository $customer_repository, BotEventRepository $bot_event_repository, BotLedgerEntryRepository $bot_ledger_entry_repository)
    {
        $this->repository                  = $repository;
        $this->transaction_repository      = $transaction_repository;
        $this->swap_repository = $swap_repository;
        $this->customer_repository = $customer_repository;
        $this->bot_ledger_entry_repository = $bot_ledger_entry_repository;
        $this->bot_event_repository        = $bot_event_repository;
    }

    /**
     * Handle the command.
     *
     * @param  DeleteBot  $command
     * @return void
     */
    public function handle(DeleteBot $command)
    {
        $bot = $command->bot;

        $driver_name = DB::getDriverName();
        if ($driver_name == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // delete all swaps entries
        foreach ($this->swap_repository->findByBotId($bot['id']) as $swap) {
            // delete all customers for this swap
            foreach ($this->customer_repository->findBySwapId($swap['id']) as $customer) {
                $this->customer_repository->delete($customer);
            }

            $this->swap_repository->delete($swap);
        };

        // delete all bot transaction entries
        foreach ($this->transaction_repository->findByBotId($bot['id']) as $transaction) {
            $this->transaction_repository->delete($transaction);
        };

        // delete all bot ledger entries
        foreach ($this->bot_ledger_entry_repository->findByBotId($bot['id']) as $bot_ledger_entry) {
            $this->bot_ledger_entry_repository->delete($bot_ledger_entry);
        };

        // delete all bot events
        foreach ($this->bot_event_repository->findByBotId($bot['id']) as $bot_event) {
            $this->bot_event_repository->delete($bot_event);
        };

        if ($driver_name == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }


        // delete the bot
        $this->repository->delete($bot);

        return null;
    }

}
