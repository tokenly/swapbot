<?php

namespace Swapbot\Handlers\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\UpdateBotBalances;
use Swapbot\Events\BotBalancesUpdated;
use Swapbot\Repositories\BotRepository;
use Tokenly\XChainClient\Client;

class UpdateBotBalancesHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(Client $xchain_client, BotRepository $repository)
    {
        $this->xchain_client = $xchain_client;
        $this->repository  = $repository;

    }

    /**
     * Handle the command.
     *
     * @param  UpdateBotBalances  $command
     * @return void
     */
    public function handle(UpdateBotBalances $command)
    {
        $bot = $command->bot;

        $old_balances = $bot['balances'];

        $update_vars = [];

        // get balances
        if (!$bot['address']) { throw new Exception("This bot does not have an address yet", 1); }
        $new_balances = $this->xchain_client->getBalances($bot['address']);

        // fire an event
        $balances_were_changed = (json_encode($old_balances) != json_encode($new_balances));
        if ($balances_were_changed) {
            // update the bot
            $update_vars['balances'] = $new_balances;
            $update_vars['balances_updated_at'] = new Carbon();
            $this->repository->update($bot, $update_vars);

            Event::fire(new BotBalancesUpdated($bot, $old_balances, $new_balances));
        }

    }

}
