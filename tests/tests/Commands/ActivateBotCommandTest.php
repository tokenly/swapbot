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
        PHPUnit::assertCount(6, $calls);
        PHPUnit::assertEquals('1oLaf1CoYcVE3aH5n5XeCJcaKPPGTxnxW', $calls[1]['data']['address']);
        PHPUnit::assertEquals('receive', $calls[1]['data']['monitorType']);
        PHPUnit::assertEquals('send', $calls[2]['data']['monitorType']);

        // check the bot repository
        $repository = app('Swapbot\Repositories\BotRepository');
        $loaded_bot = $repository->findById($bot['id']);
        PHPUnit::assertEquals('xxxxxxxx-xxxx-4xxx-yxxx-111111111111', $bot['public_address_id']);
        PHPUnit::assertEquals('1oLaf1CoYcVE3aH5n5XeCJcaKPPGTxnxW', $bot['address']);
        PHPUnit::assertEquals('xxxxxxxx-xxxx-4xxx-yxxx-2222223b9aa6', $bot['public_receive_monitor_id']);
        PHPUnit::assertEquals('xxxxxxxx-xxxx-4xxx-yxxx-22222220de1c', $bot['public_send_monitor_id']);
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
