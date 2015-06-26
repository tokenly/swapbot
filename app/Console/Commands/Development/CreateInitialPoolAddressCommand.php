<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\CustomerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateInitialPoolAddressCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:create-fuel-pool';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a fuel pool in XChain';


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
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $xchain_client = app('Tokenly\XChainClient\Client');
        $result = $xchain_client->newPaymentAddress();

        $this->info("New Payment Address Created:\n".json_encode($result, 192));

        $this->info("done");
    }

}
