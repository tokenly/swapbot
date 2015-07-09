<?php

use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ForwardPayment;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\LaravelEventLog\TestingEventLog;
use \PHPUnit_Framework_Assert as PHPUnit;

class ForwardPaymentHandlerTest extends TestCase {

    use DispatchesCommands;

    protected $use_database = true;

    public function testForwardPaymentHandler()
    {
        app('Tokenly\PusherClient\Mock\MockBuilder')->installPusherMockClient($this);
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient($this);

        $helper = $this->createRepositoryTestHelper();
        $helper->cleanup();

        $bot = app('BotHelper')->newSampleBot();

        // add credit
        $repo = app('Swapbot\Repositories\BotLedgerEntryRepository');
        $repo->addCredit($bot, 20, 'BTC', $this->sampleEvent($bot));

        // forward payment
        $this->dispatch(new ForwardPayment($bot, '1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD', 19, 'BTC'));

        // check ledger entries
        $loaded_models = array_values(iterator_to_array($repo->findByBot($bot)));
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(20), $loaded_models[0]['amount']);
        PHPUnit::assertEquals(CurrencyUtil::valueToSatoshis(19.0001), $loaded_models[1]['amount']);

        // check xchain calls
        PHPUnit::assertCount(1, $mock->calls);
        PHPUnit::assertEquals('1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD', $mock->calls[0]['data']['destination']);
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
