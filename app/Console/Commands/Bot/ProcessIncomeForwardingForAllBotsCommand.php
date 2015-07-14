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
use Swapbot\Commands\ProcessIncomeForwardingForAllBots;
use Swapbot\Handlers\Commands\ForwardPaymentHandler;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProcessIncomeForwardingForAllBotsCommand extends Command {

    use ConfirmableTrait;
    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:process-income-forwarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes income forwarding for all bots';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        return [
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
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        // require confirmation
        if (!$this->confirmToProceed()) { return; }

        try {
            $this->dispatch(new ProcessIncomeForwardingForAllBots());

        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }
        $this->info("done");
    }

}
