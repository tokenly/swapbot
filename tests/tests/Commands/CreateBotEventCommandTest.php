<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Commands\ActivateBot;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class CreateBotEventCommandTest extends TestCase {

    protected $use_database = true;

    public function testCreateBotEventCommand()
    {
        // make a bot
        $bot = app('BotHelper')->newSampleBot();

        // send a bot event to be created
        $level = 1;
        $event_data = ['foo' => 'bar', 'baz' => 'bar2'];
        app('Swapbot\Swap\Logger\BotEventLogger')->createBotEvent($bot, $level, $event_data);


        // check the bot event repository
        $repository = app('Swapbot\Repositories\BotEventRepository');
        $loaded_events = $repository->findByBotId($bot['id']);
        PHPUnit::assertCount(1, $loaded_events);
        $expected_event = [
            'id'        => $loaded_events[0]['uuid'],
            'level'     => $level,
            'event'     => $event_data,
            'createdAt' => $loaded_events[0]['created_at']->toIso8601String(),
            'serial'    => $loaded_events[0]['serial'],
        ];
        PHPUnit::assertEquals($expected_event, $loaded_events[0]->serializeForAPI());
    }


}
