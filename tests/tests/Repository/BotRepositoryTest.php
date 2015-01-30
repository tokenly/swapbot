<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadBot()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['name' => 'foo']);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testFindBotByMonitorID() {
        $helper = $this->createRepositoryTestHelper();

        // findByMonitorID
        $actual_bot = $helper->cleanup()->testUpdate(['monitor_id' => 'foo123456']);

        $loaded_bot = $this->app->make('Swapbot\Repositories\BotRepository')->findByMonitorID('foo123456');
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
