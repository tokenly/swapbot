<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Events\BotBalancesUpdated;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateBotBalancesCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:update-bot-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the bot account balances with XChain';



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
            ['daemon' , 'd', InputOption::VALUE_NONE, 'Sync with Counterparty Server and Bitcoind instead of with XChain'],
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
        $sync_with_daemon = $this->input->getOption('daemon');

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');

        if (strtolower($bot_id) == 'all') {
            $bots = [];
            foreach ($bot_repository->findAll() as $bot) {
                if (!$bot->isActive()) { continue; }
                $bots[] = $bot;
            }
        } else {
            $bot = $bot_repository->findByUuid($bot_id);
            if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
            if (!$bot) { throw new Exception("Unable to find bot", 1); }
            $bots = [$bot];
        }



        foreach($bots as $bot) {
            if ($sync_with_daemon) {
                $this->info("Doing server-based sync for bot ".$bot['name']." ({$bot['uuid']})");
                try {
                    $this->dispatch(new UpdateBotBalances($bot));
                } catch (Exception $e) {
                    // log any failure
                    EventLog::logError('balanceupdate.failed', $e);
                    $this->error("Failed: ".$e->getMessage());
                }

            } else {
                $this->info("Updating account balances for bot ".$bot['name']." ({$bot['uuid']})");
                
                try {
                    app('Swapbot\Swap\Processor\Util\BalanceUpdater')->syncBalances($bot);

                } catch (Exception $e) {
                    // log any failure
                    EventLog::logError('accountupdate.failed', $e);
                    $this->error("Failed: ".$e->getMessage());
                }
            }
        }


        $this->info("done");
    }

}
