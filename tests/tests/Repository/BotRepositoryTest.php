<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadBot()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['name' => 'foo',]);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testFindBotByMonitorID() {
        $helper = $this->createRepositoryTestHelper();

        // findBySendMonitorID
        // findByPublicMonitorID
        $actual_bot = $helper->cleanup()->testUpdate(['public_receive_monitor_id' => 'foo123457']);
        $loaded_bot = $this->app->make('Swapbot\Repositories\BotRepository')->findByPublicMonitorID('foo123457');
        PHPUnit::assertNotEmpty($loaded_bot);
        PHPUnit::assertEquals($actual_bot['uuid'], $loaded_bot['uuid']);

        $actual_bot = $helper->cleanup()->testUpdate(['public_send_monitor_id' => 'foo123458']);
        $loaded_bot = $this->app->make('Swapbot\Repositories\BotRepository')->findBySendMonitorID('foo123458');
        PHPUnit::assertNotEmpty($loaded_bot);
        PHPUnit::assertEquals($actual_bot['uuid'], $loaded_bot['uuid']);

    }

    public function testBadBotUUID() {
        $empty = $this->app->make('Swapbot\Repositories\BotRepository')->findByUuid('foo');
        PHPUnit::assertEmpty($empty);
    }

    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->app->make('BotHelper')->newSampleBot();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\BotRepository'));
        return $helper;
    }

}
