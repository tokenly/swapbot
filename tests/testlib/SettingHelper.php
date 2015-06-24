<?php

use Swapbot\Repositories\SettingRepository;

class SettingHelper  {

    function __construct(SettingRepository $setting_repository) {
        $this->setting_repository = $setting_repository;
    }

    public function sampleSettingVars() {
        return [
            'name'  => 'foo',
            'value' => ['bar' => 'baz', 'bar2' => 'baz2'],
        ];
    }

    public function newSampleSetting($vars=[]) {
        if (!isset($this->setting_uuid)) { $this->setting_uuid = 0; }
            else { ++$this->setting_uuid; }
        $attributes = array_replace_recursive($this->sampleSettingVars(), ['name' => 'foo'.(($this->setting_uuid > 0) ? ('_'.$this->setting_uuid) : '')], $vars);

        // create the model
        return $this->setting_repository->create($attributes);
    }

}
