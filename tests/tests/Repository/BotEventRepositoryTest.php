<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotEventRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testBotEventRepository()
    {
        $sample_bot           = app('BotHelper')->newSampleBot();
        $bot_event_helper     = app('BotEventHelper');
        $bot_event_repository = app('Swapbot\Repositories\BotEventRepository');

        $event_one_a       = $bot_event_helper->newSampleBotEvent($sample_bot, ['event' => ['name' => 'event.one', 'letter' => 'a']]);
        $event_one_b       = $bot_event_helper->newSampleBotEvent($sample_bot, ['event' => ['name' => 'event.one', 'letter' => 'b']]);
        $event_one_trickey = $bot_event_helper->newSampleBotEvent($sample_bot, ['event' => ['name' => 'something.else', 'foo' => 'event.one', 'letter' => 'b']]);
        $event_two         = $bot_event_helper->newSampleBotEvent($sample_bot, ['event' => ['name' => 'event.two']]);

        $events = $bot_event_repository->findByBotId($sample_bot['id'])->toArray();
        PHPUnit::assertCount(4, $events);

        $events = iterator_to_array($bot_event_repository->slowFindByEventName('event.one'));
        PHPUnit::assertCount(2, $events);
        PHPUnit::assertEquals($event_one_a['id'], $events[0]['id']);
        PHPUnit::assertEquals($event_one_b['id'], $events[1]['id']);
    }


}
