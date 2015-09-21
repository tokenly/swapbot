<?php

use Swapbot\Repositories\BotIndexRepository;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotIndexRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testBotIndexRepository()
    {
        $index = app('Swapbot\Repositories\BotIndexRepository');
        $helper = app('BotHelper');
        $bot = $helper->newSampleBotWithUniqueSlug();
        $index->clearIndex($bot); // clear the index field created when the bot is created

        $index->addToIndex($bot, BotIndexRepository::FIELD_NAME, 'My Bot');
        $index->addToIndex($bot, BotIndexRepository::FIELD_DESCRIPTION, 'the description');

        PHPUnit::assertEquals('My Bot', $index->indexedValue($bot, BotIndexRepository::FIELD_NAME));
        PHPUnit::assertEquals('the description', $index->indexedValue($bot, BotIndexRepository::FIELD_DESCRIPTION));
        PHPUnit::assertEquals(null, $index->indexedValue($bot, BotIndexRepository::FIELD_USERNAME));
    }

    public function testBotIndexRepositoryMultiInsert()
    {
        $index = app('Swapbot\Repositories\BotIndexRepository');
        $helper = app('BotHelper');
        $bot = $helper->newSampleBotWithUniqueSlug();
        $index->clearIndex($bot); // clear the index field created when the bot is created

        $index->addMultipleValuesToIndex($bot, [
            BotIndexRepository::FIELD_NAME        => 'My Bot',
            BotIndexRepository::FIELD_DESCRIPTION => 'the description',
        ]);

        PHPUnit::assertEquals('My Bot', $index->indexedValue($bot, BotIndexRepository::FIELD_NAME));
        PHPUnit::assertEquals('the description', $index->indexedValue($bot, BotIndexRepository::FIELD_DESCRIPTION));
        PHPUnit::assertEquals(null, $index->indexedValue($bot, BotIndexRepository::FIELD_USERNAME));
    }

    public function testClearBotIndexRepository()
    {
        $index = app('Swapbot\Repositories\BotIndexRepository');
        $helper = app('BotHelper');
        $bot = $helper->newSampleBotWithUniqueSlug();
        $index->clearIndex($bot); // clear the index field created when the bot is created

        $index->addToIndex($bot, BotIndexRepository::FIELD_NAME, 'My Bot');
        $index->addToIndex($bot, BotIndexRepository::FIELD_DESCRIPTION, 'the description');

        // clear
        $index->clearIndex($bot);

        PHPUnit::assertEquals(null, $index->indexedValue($bot, BotIndexRepository::FIELD_NAME));
        PHPUnit::assertEquals(null, $index->indexedValue($bot, BotIndexRepository::FIELD_DESCRIPTION));
        PHPUnit::assertEquals(null, $index->indexedValue($bot, BotIndexRepository::FIELD_USERNAME));
    }

    public function testFindAllByFields()
    {
        $index = app('Swapbot\Repositories\BotIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $helper->newSampleBotWithUniqueSlug();
        $index->clearIndex($bot_1); // clear the index field created when the bot is created
        $bot_2 = $helper->newSampleBotWithUniqueSlug();
        $index->clearIndex($bot_2); // clear the index field created when the bot is created

        $index->addMultipleValuesToIndex($bot_1, [
            BotIndexRepository::FIELD_NAME        => 'My Bot One',
            BotIndexRepository::FIELD_DESCRIPTION => 'the description for bot one',
        ]);

        $index->addMultipleValuesToIndex($bot_2, [
            BotIndexRepository::FIELD_NAME        => 'My Bot Two',
            BotIndexRepository::FIELD_DESCRIPTION => 'the description for bot two',
            BotIndexRepository::FIELD_USERNAME    => 'twodude',
        ]);

        $found_bots = $this->runSearch(['name' => 'My Bot One']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);

        $found_bots = $this->runSearch(['name' => 'Bot']);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);

        $found_bots = $this->runSearch(['name' => 'Bot', 'description' => 'two']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);

        $found_bots = $this->runSearch(['username' => 'twodude',]);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);

        $found_bots = $this->runSearch(['name' => 'One', 'description' => 'two', 'username' => 'twodude',]);
        PHPUnit::assertCount(0, $found_bots);

    }

    public function testBotIndexEvents()
    {
        $index = app('Swapbot\Repositories\BotIndexRepository');
        $user_helper = app('UserHelper');
        $user = $user_helper->newRandomUser(['username' => 'farmerjoe']);
        $helper = app('BotHelper');
        $bot = $helper->newSampleBot($user, [
            'name'        => 'Watermelon Bot',
            'description' => 'We grow \'em so you don\'t have to',
        ]);

        $bot_2 = $helper->newSampleBotWithUniqueSlug();

        PHPUnit::assertEquals('Watermelon Bot', $index->indexedValue($bot, BotIndexRepository::FIELD_NAME));
        PHPUnit::assertEquals('farmerjoe', $index->indexedValue($bot, BotIndexRepository::FIELD_USERNAME));

        PHPUnit::assertEquals('Sample Bot One', $index->indexedValue($bot_2, BotIndexRepository::FIELD_NAME));
    }


    protected function runSearch($attributes) {
        $bot_repo = app('Swapbot\Repositories\BotRepository');
        $test_helper = app('APITestHelper');

        if (!isset($attributes['state'])) { $attributes['state'] = '*'; }
        $request = $test_helper->createAPIRequest('GET', '/something', $attributes);
        $filter = IndexRequestFilter::createFromRequest($request, $bot_repo->buildFindAllFilterDefinition());

        return $bot_repo->findAll($filter);
    }


}
