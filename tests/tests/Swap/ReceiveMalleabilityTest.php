<?php

use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Models\Data\SwapState;
use Tokenly\LaravelEventLog\Facade\EventLog;
use \PHPUnit_Framework_Assert as PHPUnit;

class ReceiveMalleabilityTest extends TestCase {

    protected $use_database = true;



    public function testInvalidatedTransactionChangesTXIDin() {
        $swap_process_helper = app('SwapProcessHelper');
        list($swap, $unconfirmed_notification) = $swap_process_helper->setupSwap();

        // process the swap
        $swap_processor = app('Swapbot\Swap\Processor\SwapProcessor');
        $swap_processor->processSwap($swap, 300000);
        // echo "\$swap state: ".json_encode($swap['state'], 192)."\n";
        // echo "\$swap receipt: ".json_encode($swap['receipt'], 192)."\n";


        // send a notification that replaces this swap with a new txid
        $notification_helper = app('XChainNotificationHelper');
        $invalidation_notification = $notification_helper->sampleInvalidationNotification($unconfirmed_notification);
        Log::debug("\$invalidation_notification=".json_encode(['invalidTxid' => $invalidation_notification['invalidTxid'], 'replacingTxid' => $invalidation_notification['replacingTxid']], 192));

        // receive the invalidation event
        $swap_process_helper->receiveWebhook($invalidation_notification);

        // check that the swap was changed to the txid
        $reloaded_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($invalidation_notification['replacingTxid'], $reloaded_swap['receipt']['txidIn']);

    }


    public function testDoubleSpendInvalidatesSwap() {
        $swap_process_helper = app('SwapProcessHelper');
        list($swap, $unconfirmed_notification) = $swap_process_helper->setupSwap();

        // process the swap
        $swap_processor = app('Swapbot\Swap\Processor\SwapProcessor');
        $swap_processor->processSwap($swap, 300000);

        // send a notification that invalidates the swap txid in
        $notification_helper = app('XChainNotificationHelper');
        $replacing_notification = $unconfirmed_notification;
        $replacing_notification['txid'] = 'NEWREPLACINGTXID';
        $replacing_notification['transactionFingerprint'] = 'NEWREPLACINGFINGERPRINT';
        $invalidation_notification = $notification_helper->sampleInvalidationNotification($unconfirmed_notification, $replacing_notification);

        // receive the invalidation event
        $swap_process_helper->receiveWebhook($invalidation_notification);

        // check that the swap txid was not changed and that it was invalidated
        $reloaded_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($swap['receipt']['txidIn'], $reloaded_swap['receipt']['txidIn']);
        PHPUnit::assertEquals(SwapState::INVALIDATED, $reloaded_swap['state']);
    }

    public function testSwapReplacedByInvalidation() {
        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, ['state' => 'active',]);

        $swap_process_helper = app('SwapProcessHelper');
        list($swap, $unconfirmed_notification) = $swap_process_helper->setupSwap($bot);


        // second, swap already placed
        list($new_swap, $confirmed_notification) = $swap_process_helper->setupSwap($bot, [
            'txid'          => '2222000000000000000000000000000000000000000000000000000000000000',
            'confirmations' => 1,
            'confirmed'     => true,
        ]);

        // process the first swap
        $swap_processor = app('Swapbot\Swap\Processor\SwapProcessor');
        $swap_processor->processSwap($swap, 300000);

        // send a notification that invalidates the swap txid in
        $notification_helper = app('XChainNotificationHelper');
        $replacing_notification = $unconfirmed_notification;
        $replacing_notification['txid'] = '2222000000000000000000000000000000000000000000000000000000000000';
        $invalidation_notification = $notification_helper->sampleInvalidationNotification($unconfirmed_notification, $replacing_notification);

        // receive the invalidation event
        $swap_process_helper->receiveWebhook($invalidation_notification);

        // check that the original swap txid was not changed and that it was invalidated
        $reloaded_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($swap['receipt']['txidIn'], $reloaded_swap['receipt']['txidIn']);
        PHPUnit::assertEquals(SwapState::INVALIDATED, $reloaded_swap['state']);
    }

    // ------------------------------------------------------------------------
    

}
