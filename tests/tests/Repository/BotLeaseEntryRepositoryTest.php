<?php

use Swapbot\Swap\DateProvider\Facade\DateProvider;
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

    public function testAddOneMonthNewLease() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, 1);

        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(1, $loaded_models);
        PHPUnit::assertEquals($now->__toString(), $loaded_models[0]['start_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->__toString(), $loaded_models[0]['end_date']->__toString());
    }

    public function testAddLease() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, 1);
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->addMonthNoOverflow(1), 1);

        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals($now->__toString(), $loaded_models[0]['start_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->__toString(), $loaded_models[0]['end_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->__toString(), $loaded_models[1]['start_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->addMonthNoOverflow(1)->__toString(), $loaded_models[1]['end_date']->__toString());
    }

    public function testGetLastLeaseEntryForBot() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, 1);
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now->copy()->addMonthNoOverflow(1), 1);

        $loaded_model = $repo->getLastEntryForBot($bot);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->__toString(), $loaded_model['start_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->addMonthNoOverflow(1)->__toString(), $loaded_model['end_date']->__toString());
    }


    public function testExtendLease() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');
        $now = Carbon\Carbon::now();
        $repo->addNewLease($bot, $this->sampleEvent($bot), $now, 1);
        $repo->extendLease($bot, $this->sampleEvent($bot), 2);

        $loaded_model = $repo->getLastEntryForBot($bot);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->__toString(), $loaded_model['start_date']->__toString());
        PHPUnit::assertEquals($now->copy()->addMonthNoOverflow(1)->addMonthNoOverflow(1)->addMonthNoOverflow(1)->__toString(), $loaded_model['end_date']->__toString());
    }

    public function testExtendLeaseAfterExtendedExpiration() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();

        // add lease
        $repo = app('Swapbot\Repositories\BotLeaseEntryRepository');

        $now = Carbon\Carbon::now();
        $past = Carbon\Carbon::now()->subMonthNoOverflow(2);
        $repo->addNewLease($bot, $this->sampleEvent($bot), $past, 1);
        $repo->extendLease($bot, $this->sampleEvent($bot), 2);

        $loaded_model = $repo->getLastEntryForBot($bot);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals(DateProvider::now()->__toString(), $loaded_model['start_date']->__toString());
        PHPUnit::assertEquals(DateProvider::now()->addMonthNoOverflow()->addMonthNoOverflow()->__toString(), $loaded_model['end_date']->__toString());
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
