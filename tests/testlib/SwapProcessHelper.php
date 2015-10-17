<?php

use Illuminate\Support\Facades\Event;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\SwapStateEvent;
use Swapbot\Repositories\SwapRepository;
use Swapbot\Swap\DateProvider\Facade\DateProvider;

class SwapProcessHelper  {

    function __construct(SwapRepository $swap_repository) {
        $this->swap_repository = $swap_repository;
    }


    public function setupSwap($bot=null, $notification_override_vars=[]) {
        $mock_builder = app('Tokenly\XChainClient\Mock\MockBuilder');
        $mock = $mock_builder->installXChainMockClient();
        $mock_builder->setBalances([
            'default' => [
                'unconfirmed' => ['BTC' => 0],
                'confirmed'   => ['BTC' => 1, 'LTBCOIN' => 100000],
                'sending'     => ['BTC' => 0],
            ],
        ]);

        if ($bot === null) {
            $bot_helper = app('BotHelper');
            $bot_vars = $bot_helper->sampleAddressVars([
                'state' => 'active',
            ]);
            $bot = $bot_helper->newSampleBotWithUniqueSlug(null, $bot_vars);
        }
        $notification_helper = app('XChainNotificationHelper');
        $notification = $notification_helper->sampleReceiveNotificationForBot($bot, array_merge([
            'asset'             => 'BTC',
            'quantity'          => 0.0001,
            'confirmations'     => 0,
            'confirmed'         => false,
        ], $notification_override_vars));

        $transaction = app('TransactionHelper')->newSampleTransactionWithXchainNotification($notification, $bot);

        $swap_processor = app('Swapbot\Swap\Processor\SwapProcessor');
        $swap = $swap_processor->createNewSwap($bot['swaps'][0], $bot, $transaction);
        return [$swap, $notification];
    }

    public function receiveWebhook($notification) {
        $command = new ReceiveWebhook($notification);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);
    }



    public function sendSwap($bot=null, $notification_override_vars=[]) {
        // create the swap
        list($swap, $unconfirmed_notification) = $this->setupSwap($bot, $notification_override_vars);

        $txid_out = str_repeat('3', 60).sprintf('%04d', 1);

        // now send the swap
        $receipt_update_vars = [
            'type'             => 'swap',

            'quantityOut'      => 66.7,
            'assetOut'         => 'LTBCOIN',
            'confirmationsOut' => 0,
            'txidOut'          => $txid_out,

            'completedAt'      => DateProvider::now(),
            'timestamp'        => DateProvider::now(),

        ];

        $update_vars = $this->swap_repository->mergeUpdateVars($swap, ['receipt' => $receipt_update_vars]);
        $this->swap_repository->update($swap, $update_vars);

        // move the swap into the sent state
        $swap->stateMachine()->triggerEvent(SwapStateEvent::STOCK_CHECKED);
        $swap->stateMachine()->triggerEvent(SwapStateEvent::SWAP_SENT);

        return [$swap];
    }

}
