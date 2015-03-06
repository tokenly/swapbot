<?php namespace Swapbot\Handlers\Commands;

use Exception;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\DB;
use Swapbot\Commands\BillBotForTransaction;
use Swapbot\Commands\UpdateBotPaymentAccount;
use Swapbot\Repositories\TransactionRepository;
use Swapbot\Swap\Logger\BotEventLogger;

class BillBotForTransactionHandler {

    use DispatchesCommands;

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(TransactionRepository $transaction_repository, BotEventLogger $bot_event_logger)
    {
        $this->transaction_repository = $transaction_repository;
        $this->bot_event_logger       = $bot_event_logger;
    }

    /**
     * Handle the command.
     *
     * @param  BillBotForTransaction  $command
     * @return void
     */
    public function handle(BillBotForTransaction $command)
    {
        $bot = $command->bot;
        $transaction = $command->transaction;

        if ($transaction['billed_event_id']) { throw new Exception("transaction already billed", 1); }

        DB::transaction(function() use ($bot, $transaction) {
            // debit the bot's payment account (when any transaction was processed)
            $debit_amount = $bot->getTXFee();
            $is_credit = false;
            $bot_event = $this->bot_event_logger->logTransactionFee($bot, $debit_amount, $transaction['id']);

            // save the payment
            $this->dispatch(new UpdateBotPaymentAccount($bot, $debit_amount, $is_credit, $bot_event));

            // save the billing event id
            $this->transaction_repository->update($transaction, [
                'billed_event_id' => $bot_event['id'],
            ]);
        });
    }

}
