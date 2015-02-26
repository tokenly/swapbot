<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\UpdateBotPaymentAccount as UpdateBotPaymentAccountCommand;
use Swapbot\Swap\Logger\BotEventLogger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateBotPaymentAccount extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:update-bot-payment-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applies a payment to a bot.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            $bot_id = $this->input->getArgument('bot-id');
            $amount = $this->input->getArgument('amount');

            $mock_xchain = $this->input->getOption('mock-xchain');
            $is_credit = !$this->input->getOption('debit');
            $message = $this->input->getOption('message');

            if ($mock_xchain) {
                $this->comment('Mocking XChain Calls');
                $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();
            }

            $bot_repository = app('Swapbot\Repositories\BotRepository');

            $bot = $bot_repository->findByID($bot_id);
            if (!$bot) {
                // try uuid
                $bot = $bot_repository->findByUuid($bot_id);
            }
            if (!$bot) { throw new Exception("Unable to find bot", 1); }

            $this->info('Applying payment of '.$amount.' to bot '.$bot['name']);;

            // apply a payment
            // echo "\$bot:\n".json_encode($bot, 192)."\n";
            $bot_event = app('Swapbot\Swap\Logger\BotEventLogger')->logManualPayment($bot, $amount, $is_credit, $message);
            $this->dispatch(new UpdateBotPaymentAccountCommand($bot, $amount, $is_credit, $bot_event));

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
            ['amount', InputArgument::REQUIRED, 'Amount'],
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
            ['mock-xchain', 'x', InputOption::VALUE_NONE, 'Mock XChain Transactions'],
            ['debit', 'd', InputOption::VALUE_NONE, 'Is a debit'],
            ['message', 'm', InputOption::VALUE_OPTIONAL, 'Event log message'],
        ];
    }

}
