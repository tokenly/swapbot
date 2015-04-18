<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class ConsumerRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testConsumerRepository()
    {
        $create_model_fn = function() {
            return $this->app->make('ConsumerHelper')->newSampleConsumer();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\ConsumerRepository'));
        // $helper->use_uuid = false;

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['email' => 'foo@tokenly.co']);
        $helper->cleanup()->testDelete();
    }

    public function testFindConsumersBySwap()
    {
        $swap1 = app('SwapHelper')->newSampleSwap();
        $swap2 = app('SwapHelper')->newSampleSwap();
        $consumer_1 = app()->make('ConsumerHelper')->newSampleConsumer($swap1);
        $consumer_2 = app()->make('ConsumerHelper')->newSampleConsumer($swap1, ['email' => 'dude2@tokenly.co',]);
        $consumer_3 = app()->make('ConsumerHelper')->newSampleConsumer($swap2);

        $consumer_repository = app('Swapbot\Repositories\ConsumerRepository');
        

        $loaded_consumers = $consumer_repository->findBySwap($swap1);
        PHPUnit::assertCount(2, $loaded_consumers);
        PHPUnit::assertEquals($consumer_1['id'], $loaded_consumers[0]['id']);
        PHPUnit::assertEquals($consumer_2['id'], $loaded_consumers[1]['id']);

        $loaded_consumers = $consumer_repository->findBySwap($swap2);
        PHPUnit::assertCount(1, $loaded_consumers);
        PHPUnit::assertEquals($consumer_3['id'], $loaded_consumers[0]['id']);
    }



}
