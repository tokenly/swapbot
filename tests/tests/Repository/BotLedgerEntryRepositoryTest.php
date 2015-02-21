<?php

use Tokenly\CurrencyLib\CurrencyUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotLedgerEntryRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadBotLedgerEntry()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testAddCredit() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit

        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($bot, 100, $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, $this->sampleEvent($bot));

        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(100), $loaded_models[0]['amount']);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(200), $loaded_models[1]['amount']);
        PHPUnit::assertTrue($loaded_models[0]['is_credit']);
    }

    public function testAddDebit() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addDebit($bot, 5000, $this->sampleEvent($bot));
        $repo->addDebit($bot, 6000, $this->sampleEvent($bot));

        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(5000), $loaded_models[0]['amount']);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(6000), $loaded_models[1]['amount']);
        PHPUnit::assertFalse($loaded_models[0]['is_credit']);
    }


    public function testSums() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($bot, 100, $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, $this->sampleEvent($bot));

        PHPUnit::assertEquals(300, $repo->sumCreditsAndDebits($bot));
    }

    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->app->make('BotLedgerEntryHelper')->newSampleBotLedgerEntry();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\BotLedgerEntryRepository'));
        return $helper;
    }

    protected function sampleEvent($bot) {
        return app('BotEventHelper')->newSampleBotEvent($bot);
    }

}
