<?php

use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;
use Swapbot\Models\Bot;
use Swapbot\Models\Data\BotPaymentState;
use Swapbot\Models\Data\BotPaymentStateEvent;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class AdminNotificationTest extends TestCase {

    protected $use_database = true;

    public function testBotLowFuelNotifications()
    {
        // install mocks
        $mailer_recorder = $this->installMocks();

        // setup
        list($bot, $state_machine) = $this->makeActiveBotAndStateMachine();

        // fuel is exhausted
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUEL_EXHAUSTED, BotState::LOW_FUEL);


        // check that a low fuel email was sent
        PHPUnit::assertCount(1, $mailer_recorder->emails);
        $email = $mailer_recorder->emails[0];
        list($email_address, $name) = each($email['to']);
        PHPUnit::assertEquals('sample@tokenly.co', $email_address);
        PHPUnit::assertEquals('Sample User', $name);
        PHPUnit::assertContains('Your Swapbot is Low on Fuel', $email['subject']);
        PHPUnit::assertContains('out of BTC fuel', $email['body']);
        PHPUnit::assertContains('1xxxxxxxxxxxxxxxxxxxxxxxx', $email['body']);
    }

    public function testBotUnpaidNotifications()
    {
        // install mocks
        $mailer_recorder = $this->installMocks();

        // setup
        list($bot, $state_machine) = $this->makeActiveBotAndPaymentStateMachine();

        // go to NOTICE state
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);

        // test payment state email
        $this->runPaymentEmailTest($mailer_recorder->emails, 'sample@tokenly.co', 'Sample User', 'Your Swapbot Expires in a Couple Weeks', ['will expire in a couple of weeks','this Swapbot will not be able to process any swaps']);


        // clear emails
        $mailer_recorder->emails = [];

        // go to SOON state
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_SOON, BotPaymentState::SOON);

        // test payment state email
        $this->runPaymentEmailTest($mailer_recorder->emails, 'sample@tokenly.co', 'Sample User', 'Your Swapbot is Expiring Soon', ['will expire in a week','this Swapbot will not be able to process any swaps']);


        // clear emails
        $mailer_recorder->emails = [];

        // go to URGENT state
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_URGENT, BotPaymentState::URGENT);

        // test payment state email
        $this->runPaymentEmailTest($mailer_recorder->emails, 'sample@tokenly.co', 'Sample User', 'Your Swapbot will Expire Within A Day', ['will expire within 24 hours','this Swapbot will not be able to process any swaps']);



        // clear emails
        $mailer_recorder->emails = [];

        // go to PAST_DUE state
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_PAST_DUE, BotPaymentState::PAST_DUE);

        // test payment state email
        $this->runPaymentEmailTest($mailer_recorder->emails, 'sample@tokenly.co', 'Sample User', 'Your Swapbot Has Expired', ['is now unpaid','not processing any swaps']);


    }


    public function testNoAdminEmailWhenPrefsSet()
    {
        // install mocks
        $mailer_recorder = $this->installMocks();

        // setup
        list($bot, $state_machine) = $this->makeActiveBotAndPaymentStateMachine();
        app('Swapbot\Repositories\UserRepository')->update($bot->user, ['email_preferences' => ['adminEvents' => false]]);

        // go to NOTICE state
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_NOTICE, BotPaymentState::NOTICE);

        // No emails are sent because opt-out prefs are set
        PHPUnit::assertCount(0, $mailer_recorder->emails);
    }


    ////////////////////////////////////////////////////////////////////////
    
    protected function installMocks() {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);
        $mailer_recorder = app('ScenarioRunner')->installMockMailer();
        return $mailer_recorder;
    }

    protected function makeActiveBotAndStateMachine() {
        // make a sample bot
        $bot = app('BotHelper')->newSampleBot();

        // build a statemachine and go to active
        $state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($bot);
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FIRST_MONTHLY_FEE_PAID, BotState::LOW_FUEL);
        $this->runTransitionTest($state_machine, $bot, BotStateEvent::FUELED, BotState::ACTIVE);

        return [$bot, $state_machine];
    }

    protected function makeActiveBotAndPaymentStateMachine() {
        // make a sample bot
        $bot = app('BotHelper')->newSampleBot();

        // build a statemachine and go to active
        $state_machine = app('Swapbot\Statemachines\BotPaymentStateMachineFactory')->buildStateMachineFromBot($bot);
        $this->runPaymentStateTransitionTest($state_machine, $bot, BotPaymentStateEvent::ENTERED_OK, BotPaymentState::OK);

        return [$bot, $state_machine];
    }

    protected function runPaymentStateTransitionTest($state_machine, $bot, $event, $new_state) {
        return $this->runTransitionTest($state_machine, $bot, $event, $new_state, 'payment_state');
    }

    protected function runTransitionTest($state_machine, $bot, $event, $new_state, $state_field='state') {
        $state_machine->triggerEvent($event);

        // now we are in the low fuel state
        PHPUnit::assertEquals($new_state, $state_machine->getCurrentState()->getName());
        PHPUnit::assertEquals($new_state, $bot[$state_field]);
        PHPUnit::assertEquals($new_state, app('Swapbot\Repositories\BotRepository')->findByID($bot['id'])[$state_field]);
    }

    protected function runPaymentEmailTest($emails, $expected_email, $expected_name, $expected_subject, $expected_body_texts) {
        // check that an unpaid email was sent
        PHPUnit::assertCount(1, $emails);
        $email = $emails[0];
        list($email_address, $name) = each($email['to']);
        PHPUnit::assertEquals($expected_email, $email_address);
        PHPUnit::assertEquals($expected_name, $name);
        PHPUnit::assertContains($expected_subject, $email['subject']);
        foreach($expected_body_texts as $expected_body_text) {
            PHPUnit::assertContains($expected_body_text, $email['body']);
        }
    }



}
