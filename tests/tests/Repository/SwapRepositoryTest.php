<?php

use Tokenly\CurrencyLib\CurrencyUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadSwap()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['receipt' => 'foo', 'state' => 'partying']);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testFindSwapsByBotID()
    {
        $helper = $this->createRepositoryTestHelper();

        $bot = app('BotHelper')->newSampleBot();
        $swap1 = app('SwapHelper')->newSampleSwap($bot);
        $swap2 = app('SwapHelper')->newSampleSwap($bot);

        $bot2 = app('BotHelper')->newSampleBot();
        $swap3 = app('SwapHelper')->newSampleSwap($bot2);

        $loaded_models = array_values(iterator_to_array(app('Swapbot\Repositories\SwapRepository')->findByBot($bot)));
        PHPUnit::assertNotEmpty($loaded_models);
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals($swap1->toArray(), $loaded_models[0]->toArray());
        PHPUnit::assertEquals($swap2->toArray(), $loaded_models[1]->toArray());
    }


    public function testFindSwapByBotIDTransactionIDAndName()
    {
        $helper = $this->createRepositoryTestHelper();

        $bot = app('BotHelper')->newSampleBot();
        $bot2 = app('BotHelper')->newSampleBot();
        $transaction = app('TransactionHelper')->newSampleTransaction($bot);

        $swap1 = app('SwapHelper')->newSampleSwap($bot, $transaction);
        $swap2 = app('SwapHelper')->newSampleSwap($bot, $transaction, ['name' => 'BTC:SOUP']);
        $swap3 = app('SwapHelper')->newSampleSwap($bot2, $transaction);

        $loaded_model = app('Swapbot\Repositories\SwapRepository')->findByBotIDTransactionIDAndName($bot['id'], $transaction['id'], 'SOUP:BTC');
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($swap1->toArray(), $loaded_model->toArray());

        $loaded_model = app('Swapbot\Repositories\SwapRepository')->findByBotIDTransactionIDAndName($bot['id'], $transaction['id'], 'BTC:SOUP');
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($swap2->toArray(), $loaded_model->toArray());

        $loaded_model = app('Swapbot\Repositories\SwapRepository')->findByBotIDTransactionIDAndName($bot2['id'], $transaction['id'], 'SOUP:BTC');
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals($swap3->toArray(), $loaded_model->toArray());
    }



    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->app->make('SwapHelper')->newSampleSwap();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\SwapRepository'));
        return $helper;
    }

}
