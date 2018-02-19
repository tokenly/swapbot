<?php

use Carbon\Carbon;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
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

    public function testFindBotByUserIDAndUUID() {
        $bot_helper = app('BotHelper');
        $user = app('UserHelper')->newSampleUser();
        $expected_bot = $bot_helper->newSampleBotWithUniqueSlug($user);

        $repo = app('Swapbot\Repositories\BotRepository');

        // load with bad user id
        $loaded_bot = $repo->findByUuidAndUserID($expected_bot['uuid'], 'baduser');
        PHPUnit::assertNull($loaded_bot);

        // load with good user id
        $loaded_bot = $repo->findByUuidAndUserID($expected_bot['uuid'], $user['id']);
        PHPUnit::assertNotNull($loaded_bot);
        PHPUnit::assertEquals($expected_bot->toArray(), normalize_updated_date($loaded_bot->toArray(), $expected_bot->toArray()));

    }

    public function testBadBotUUID() {
        $empty = $this->app->make('Swapbot\Repositories\BotRepository')->findByUuid('foo');
        PHPUnit::assertEmpty($empty);
    }

    public function testBotHashUpdates() {
        $helper = $this->createRepositoryTestHelper();

        // lock in a date
        DateProvider::setNow(Carbon::now());

        $model = $helper->testLoad();
        PHPUnit::assertNotEmpty($model['hash']);
        PHPUnit::assertEquals($model['hash'], $model->buildHash());
        PHPUnit::assertEquals(DateProvider::now()->__toString(), $model['last_changed_at']->__toString());

        // update a hash
        $last_now = Carbon::now()->modify('+5 minutes');
        DateProvider::setNow($last_now);
        $repo = app('Swapbot\Repositories\BotRepository');
        $old_hash = $model['hash'];
        $result = $repo->update($model, ['description' => 'foo2']);
        PHPUnit::assertNotEmpty($model['hash']);
        PHPUnit::assertNotEquals($old_hash, $model['hash']);
        PHPUnit::assertEquals(DateProvider::now()->__toString(), $model['last_changed_at']->__toString());

        // update a hash again
        DateProvider::setNow(Carbon::now()->modify('+5 minutes'));
        $repo = app('Swapbot\Repositories\BotRepository');
        $old_hash = $model['hash'];
        $result = $repo->update($model, ['description' => 'foo2']);
        PHPUnit::assertNotEmpty($model['hash']);
        PHPUnit::assertEquals($old_hash, $model['hash']);
        PHPUnit::assertEquals($last_now->__toString(), $model['last_changed_at']->__toString());
    }

    public function testBotIncomeForwardingAddresses() {
        $bot = app('BotHelper')->newSampleBotWithUniqueSlug();
        PHPUnit::assertEquals(['1JY6wKwW5D5Yy64RKA7rDyyEdYrLSD3J6B'], $bot->getAllIncomeForwardingAddresses());

        $bot = app('BotHelper')->newSampleBotWithUniqueSlug(null, [
            'income_rules'               => [
                [
                    'asset'         => 'BTC',
                    'minThreshold'  => 10.0,
                    'paymentAmount' => 2.0,
                    'address'       => '1JY6wKwW5D5Yy64RKA7rDyyEdYrLSD3J6B',
                ],
                [
                    'asset'         => 'LTBCOIN',
                    'minThreshold'  => 10000,
                    'paymentAmount' => 2000,
                    'address'       => '1JY6wKwW5D5Yy64RKA7rDyyEdYrLSD3J6B',
                ],
                [
                    'asset'         => 'XCP',
                    'minThreshold'  => 50,
                    'paymentAmount' => 10,
                    'address'       => 'address000000000000000000000000002',
                ],
            ],
        ]);
        PHPUnit::assertEquals(['1JY6wKwW5D5Yy64RKA7rDyyEdYrLSD3J6B', 'address000000000000000000000000002'], $bot->getAllIncomeForwardingAddresses());

    }


    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->app->make('BotHelper')->newSampleBotWithUniqueSlug();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\BotRepository'));
        return $helper;
    }

}
