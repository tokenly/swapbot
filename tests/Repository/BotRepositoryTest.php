<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BotRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadBot()
    {
        $create_model_fn = function() {
            return $this->app->make('BotHelper')->newSampleBot();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\BotRepository'));

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['name' => 'foo']);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testBadBotUUID() {
        $empty = $this->app->make('Swapbot\Repositories\BotRepository')->findByUuid('foo');
        PHPUnit::assertEmpty($empty);
    }


}
