<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Commands\ActivateBot;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class ActivateBotCommandTest extends TestCase {

    protected $use_database = true;

    public function testActivateBotCommand()
    {
        // mock xchain client
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a bot
        $bot = app('BotHelper')->newSampleBot();

        // send a bot to be activated
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch(new ActivateBot($bot));

        // check the calls
        $calls = $mock->calls;
        PHPUnit::assertNotEmpty($calls);
        PHPUnit::assertCount(2, $calls);
        PHPUnit::assertEquals('1oLaf1CoYcVE3aH5n5XeCJcaKPPGTxnxW', $calls[1]['data']['address']);

        // check the bot repository
        $repository = app('Swapbot\Repositories\BotRepository');
        $loaded_bot = $repository->findById($bot['id']);
        PHPUnit::assertEquals('xxxxxxxx-xxxx-4xxx-yxxx-111111111111', $bot['payment_address_id']);
        PHPUnit::assertEquals('1oLaf1CoYcVE3aH5n5XeCJcaKPPGTxnxW', $bot['address']);
        PHPUnit::assertEquals('xxxxxxxx-xxxx-4xxx-yxxx-222222222222', $bot['monitor_id']);
        PHPUnit::assertTrue($bot['active']);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage already active
     */
    public function testDontActivateBotTwice()
    {
        // mock xchain client
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        // make a bot
        $bot = app('BotHelper')->newSampleBot();

        // send a bot to be activated
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch(new ActivateBot($bot));

        // send a bot to be activated again
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch(new ActivateBot($bot));
    }

}
