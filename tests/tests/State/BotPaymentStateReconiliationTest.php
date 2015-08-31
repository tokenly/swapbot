<?php

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;
use Swapbot\Commands\ReconcileBotPaymentState;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Models\Data\BotPaymentStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotPaymentStateReconiliationTest extends TestCase {

    use DispatchesJobs;

    protected $use_database = true;


    public function testBotPaymentStatePastDueReconciliation()
    {
        $bot = $this->setupBot();
        $this->dispatch(new ReconcileBotPaymentState($bot));

        // go to past due
        PHPUnit::assertEquals(BotPaymentState::PAST_DUE, $bot['payment_state']);

        // add lease
        $lease_repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon::now();
        $lease_repo->addNewLease($bot, $this->sampleEvent($bot), $now, 1);


        // go to ok
        $this->dispatch(new ReconcileBotPaymentState($bot));
        // $bot = $this->reloadBot($bot);
        PHPUnit::assertEquals(BotPaymentState::OK, $bot['payment_state']);

    }

    public function testBotPaymentStateNoticeReconciliation()
    {
        $bot = $this->setupBot();

        // add lease that expires in 2 weeks
        $lease_repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon::now();
        $lease_repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->addWeeks(2)->subMonths(1), 1);


        $this->dispatch(new ReconcileBotPaymentState($bot));

        // went to NOTICE
        PHPUnit::assertEquals(BotPaymentState::NOTICE, $bot['payment_state']);

    }

    public function testBotPaymentStateSoonReconciliation()
    {
        $bot = $this->setupBot();

        // add lease that expires in 1 week
        $lease_repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon::now();
        $lease_repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->addWeeks(1)->subMonths(1), 1);


        $this->dispatch(new ReconcileBotPaymentState($bot));

        // went to SOON
        PHPUnit::assertEquals(BotPaymentState::SOON, $bot['payment_state']);

    }

    public function testBotPaymentStateUrgentReconciliation()
    {
        $bot = $this->setupBot();

        // add lease that expires in 1 week
        $lease_repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon::now();
        $lease_repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->addDays(1)->subMonths(1), 1);


        $this->dispatch(new ReconcileBotPaymentState($bot));

        // went to URGENT
        PHPUnit::assertEquals(BotPaymentState::URGENT, $bot['payment_state']);

        // reconcile again just to check
        $this->dispatch(new ReconcileBotPaymentState($bot));

        // still urgent
        PHPUnit::assertEquals(BotPaymentState::URGENT, $bot['payment_state']);


    }

    ////////////////////////////////////////////////////////////////////////
    
    protected function setupBot() {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);
        $bot = app('BotHelper')->newSampleBot();

        return $bot;
    }

    protected function sampleEvent($bot) {
        return app('BotEventHelper')->newSampleBotEvent($bot);
    }

    protected function reloadBot($bot) {
        return app('Swapbot\Repositories\BotRepository')->findByID($bot['id']);
    }

}

/*

BotPaymentState::NONE
BotPaymentState::PAST_DUE
BotPaymentState::URGENT
BotPaymentState::SOON
BotPaymentState::NOTICE
BotPaymentState::OK

 */
