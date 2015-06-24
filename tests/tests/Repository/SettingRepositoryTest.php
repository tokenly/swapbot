<?php

use Swapbot\Swap\Settings\Facade\Settings;
use \PHPUnit_Framework_Assert as PHPUnit;

class SettingRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testSettingRepository()
    {
        $setting_helper = $this->app->make('SettingHelper');
        $create_model_fn = function() use ($setting_helper) {
            return $setting_helper->newSampleSetting();
        };
        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('Swapbot\Repositories\SettingRepository'));

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['value' => ['bar3' => 'baz3']]);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testFindSettingByName()
    {
        $setting_helper = $this->app->make('SettingHelper');
        $setting_repository = $this->app->make('Swapbot\Repositories\SettingRepository');

        $setting1 = $setting_helper->newSampleSetting(['name' => 'setting_001']);
        $setting2 = $setting_helper->newSampleSetting(['name' => 'setting_002']);
        $setting3 = $setting_helper->newSampleSetting(['name' => 'setting_003']);

        $loaded_setting1 = $setting_repository->findByName('setting_001', $setting1['name']);
        PHPUnit::assertEquals($setting1['name'], $loaded_setting1['name']);

        $loaded_setting2 = $setting_repository->findByName('setting_002', $setting1['name']);
        PHPUnit::assertEquals($setting2['name'], $loaded_setting2['name']);
    }

    public function testCreateOrUpdateSetting()
    {
        $setting_helper = $this->app->make('SettingHelper');
        $setting_repository = $this->app->make('Swapbot\Repositories\SettingRepository');

        $setting1 = $setting_repository->createOrUpdate('setting_001', ['foo' => 'bar001']);
        $loaded_setting1 = $setting_repository->findByName('setting_001', $setting1['name']);
        PHPUnit::assertEquals($setting1['name'], $loaded_setting1['name']);
        PHPUnit::assertEquals(['foo' => 'bar001'], $loaded_setting1['value']);


        $setting1 = $setting_repository->createOrUpdate('setting_001', ['foo' => 'bar002']);
        $loaded_setting1 = $setting_repository->findByName('setting_001', $setting1['name']);
        PHPUnit::assertEquals($setting1['name'], $loaded_setting1['name']);
        PHPUnit::assertEquals(['foo' => 'bar002'], $loaded_setting1['value']);
    }

    public function testSettingsFacade()
    {
        $setting_helper = $this->app->make('SettingHelper');
        $setting_repository = $this->app->make('Swapbot\Repositories\SettingRepository');

        $setting1 = $setting_repository->createOrUpdate('setting_001', ['foo' => 'bar001', 'a' => ['b1' => 'c1', 'b2' => 'c2', ]]);

        PHPUnit::assertEquals(['foo' => 'bar001', 'a' => ['b1' => 'c1', 'b2' => 'c2', ]], Settings::get('setting_001'));
        PHPUnit::assertEquals(['b1' => 'c1', 'b2' => 'c2', ], Settings::get('setting_001.a'));
        PHPUnit::assertEquals('c1', Settings::get('setting_001.a.b1'));
        PHPUnit::assertEquals('c2', Settings::get('setting_001.a.b2'));
    }

}
