<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Swapbot\Commands\DeleteSwap;
use Swapbot\Repositories\BotEventRepository;
use Swapbot\Repositories\CustomerRepository;
use Swapbot\Repositories\SwapRepository;

class DeleteSwapHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(SwapRepository $swap_repository, CustomerRepository $customer_repository, BotEventRepository $bot_event_repository)
    {
        $this->swap_repository = $swap_repository;
        $this->customer_repository = $customer_repository;
        $this->bot_event_repository        = $bot_event_repository;
    }

    /**
     * Handle the command.
     *
     * @param  DeleteSwap  $command
     * @return void
     */
    public function handle(DeleteSwap $command)
    {
        $swap = $command->swap;

        $driver_name = DB::getDriverName();
        if ($driver_name == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=0;'); }

        // delete all customers for this swap
        foreach ($this->customer_repository->findBySwapId($swap['id']) as $customer) {
            $this->customer_repository->delete($customer);
        }

        // delete all bot events for this swap
        foreach ($this->bot_event_repository->findBySwapId($swap['id']) as $bot_event) {
            $this->bot_event_repository->delete($bot_event);
        };

        if ($driver_name == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); }

        $this->swap_repository->delete($swap);

    }

}
