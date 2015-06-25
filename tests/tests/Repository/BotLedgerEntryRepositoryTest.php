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
        $repo->addCredit($bot, 100, 'BTC', $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, 'BTC', $this->sampleEvent($bot));

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
        $repo->addDebit($bot, 5000, 'BTC', $this->sampleEvent($bot));
        $repo->addDebit($bot, 6000, 'BTC', $this->sampleEvent($bot));

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
        $repo->addCredit($bot, 100, 'BTC', $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, 'BTC', $this->sampleEvent($bot));

        PHPUnit::assertEquals(300, $repo->sumCreditsAndDebits($bot, 'BTC'));
    }

    public function testMultipleAssetSums() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($bot, 100, 'BTC', $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, 'BTC', $this->sampleEvent($bot));

        $repo->addCredit($bot, 1000, 'TOKENLY', $this->sampleEvent($bot));
        $repo->addDebit($bot,   200, 'TOKENLY', $this->sampleEvent($bot));

        PHPUnit::assertEquals(300, $repo->sumCreditsAndDebits($bot, 'BTC'));
        PHPUnit::assertEquals(800, $repo->sumCreditsAndDebits($bot, 'TOKENLY'));
    }

    public function testSumCreditsAndDebitsByAsset() {
        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($bot, 100, 'BTC', $this->sampleEvent($bot));
        $repo->addCredit($bot, 200, 'BTC', $this->sampleEvent($bot));

        $repo->addCredit($bot, 1000, 'TOKENLY', $this->sampleEvent($bot));
        $repo->addDebit($bot,   200, 'TOKENLY', $this->sampleEvent($bot));

        $repo->addDebit($bot,   1,   'SWAPBOTMONTH', $this->sampleEvent($bot));

        $expected_sums = [
            'BTC'            => 300,
            'TOKENLY'        => 800,
            'SWAPBOTMONTH' => -1,
        ];
        PHPUnit::assertEquals($expected_sums, $repo->sumCreditsAndDebitsByAsset($bot));
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
