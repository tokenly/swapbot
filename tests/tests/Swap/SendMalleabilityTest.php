<?php

use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Models\Data\SwapState;
use Tokenly\LaravelEventLog\Facade\EventLog;
use \PHPUnit_Framework_Assert as PHPUnit;

class SendMalleabilityTest extends TestCase {

    protected $use_database = true;

    public function testInvalidatedSendChangesTXIDOut() {
        $notification_helper = app('XChainNotificationHelper');
        $swap_process_helper = app('SwapProcessHelper');

        $bot_helper = app('BotHelper');
        $bot = $bot_helper->newSampleBotWithUniqueSlug(null, $bot_helper->sampleAddressVars(['state' => 'active',]));

        list($swap) = $swap_process_helper->sendSwap($bot);

        $bad_sent_txid = $swap['receipt']['txidOut'];

        // now receive an invalidation for that txid
        $invalid_notification = $notification_helper->sampleSendNotificationForBot($bot, ['txid' => $bad_sent_txid]);
        $invalidation_notification = $notification_helper->sampleInvalidationNotification($invalid_notification);
        Log::debug("\$invalidation_notification=".json_encode(['invalidTxid' => $invalidation_notification['invalidTxid'], 'replacingTxid' => $invalidation_notification['replacingTxid']], 192));

        // receive the invalidation event
        $swap_process_helper->receiveWebhook($invalidation_notification);

        // make sure that the txidOut was changed
        $reloaded_swap = app('Swapbot\Repositories\SwapRepository')->findByID($swap['id']);
        PHPUnit::assertEquals($invalidation_notification['replacingTxid'], $reloaded_swap['receipt']['txidOut']);

    }


    // ------------------------------------------------------------------------
    

}
