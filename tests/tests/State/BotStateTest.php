<?php

use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotStateTest extends TestCase {

    protected $use_database = true;

    public function testBotStateTransitions()
    {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);

        // install xchain mocks
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a sample bot
        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // build a statemachine
        $state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($bot);

        // no transition yet
        PHPUnit::assertEquals(BotState::BRAND_NEW, $state_machine->getCurrentState()->getName());

        // transition to LOW_FUEL
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FIRST_MONTHLY_FEE_PAID, BotState::LOW_FUEL);

        // go to paying state
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::LEASE_EXPIRED, BotState::PAYING);

        // receive payment again
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::MONTHLY_FEE_PAID, BotState::ACTIVE);

        // fuel is exhausted
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUEL_EXHAUSTED, BotState::LOW_FUEL);

        // receive fuel
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUELED, BotState::ACTIVE);

        // go to paying state
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::LEASE_EXPIRED, BotState::PAYING);

        // go to unpaid state
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::PAYMENT_EXHAUSTED, BotState::UNPAID);

        // receive payment again
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::MONTHLY_FEE_PAID, BotState::ACTIVE);
    }


    public function testBotShutdownStateTransitions()
    {
        list($bot, $state_machine) = $this->setupActiveBotForStateTest();

        // check active to shutdown

        // ACTIVE -> SHUTTING_DOWN
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::START_SHUTDOWN, BotState::SHUTTING_DOWN);

        // SHUTTING_DOWN -> SHUTDOWN
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::COMPLETE_SHUTDOWN, BotState::SHUTDOWN);



        // check shutting down an UNPAID bot
        list($bot, $state_machine) = $this->setupActiveBotForStateTest();

        // ACTIVE -> PAYING -> UNPAID
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::LEASE_EXPIRED, BotState::PAYING);
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::PAYMENT_EXHAUSTED, BotState::UNPAID);

        // UNPAID -> SHUTTING_DOWN
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::START_SHUTDOWN, BotState::SHUTTING_DOWN);



        // check shutting down a LOW_FUEL bot
        list($bot, $state_machine) = $this->setupActiveBotForStateTest();

        // ACTIVE -> LOW_FUEL
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUEL_EXHAUSTED, BotState::LOW_FUEL);

        // LOW_FUEL -> SHUTTING_DOWN
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::START_SHUTDOWN, BotState::SHUTTING_DOWN);

    }

    protected function setupActiveBotForStateTest() {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);

        // install xchain mocks
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a sample bot
        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // build a statemachine
        $state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($bot);

        // make active
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FIRST_MONTHLY_FEE_PAID, BotState::LOW_FUEL);
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUELED, BotState::ACTIVE);

        return [$bot, $state_machine];
    }

    protected function runTransitionTest($state_machine, $bot, $event, $new_state) {
        $state_machine->triggerEvent($event);

        // now we are in the low fuel state
        PHPUnit::assertEquals($new_state, $state_machine->getCurrentState()->getName());
        PHPUnit::assertEquals($new_state, $bot['state']);
        PHPUnit::assertEquals($new_state, app('Swapbot\Repositories\BotRepository')->findByID($bot['id'])['state']);
    }

}
