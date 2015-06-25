<?php

use Tokenly\CurrencyLib\CurrencyUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotLeaseEntryRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadBotLeaseEntry()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testAddLease() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, '1 month');
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->modify('1 month'), '1 month');

        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals($now, $loaded_models[0]['start_date']);
        PHPUnit::assertEquals($now->copy()->modify('1 month'), $loaded_models[0]['end_date']);
        PHPUnit::assertEquals($now->copy()->modify('1 month'), $loaded_models[1]['start_date']);
        PHPUnit::assertEquals($now->copy()->modify('2 months'), $loaded_models[1]['end_date']);
    }

    public function testGetLastLeaseEntryForBot() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, '1 month');
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->modify('1 month'), '1 month');

        $loaded_model = $repo->getLastEntryForBot($bot);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($now->copy()->modify('1 month'), $loaded_model['start_date']);
        PHPUnit::assertEquals($now->copy()->modify('2 months'), $loaded_model['end_date']);
    }


    public function testExtendLease() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, '1 month');
        $repo->extendLease($bot, $this->sampleEvent($bot), '2 months');

        $loaded_model = $repo->getLastEntryForBot($bot);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($now->copy()->modify('1 month'), $loaded_model['start_date']);
        PHPUnit::assertEquals($now->copy()->modify('3 months'), $loaded_model['end_date']);
    }



    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->app->make('BotLeaseEntryHelper')->newSampleBotLeaseEntry();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\BotLeaseEntryRepository'));
        return $helper;
    }

    protected function sampleEvent($bot) {
        return app('BotEventHelper')->newSampleBotEvent($bot);
    }

}
