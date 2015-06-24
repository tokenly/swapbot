<?php

namespace Swapbot\Swap\Settings;

use Exception;
use Swapbot\Repositories\SettingRepository;

/**
* Settings facade
*/
class Settings {

    public function __construct(SettingRepository $setting_repository) {
        $this->setting_repository = $setting_repository;

    }

    public function get($settings_key) {
        $name = $this->extractSettingsName($settings_key);
        $setting = $this->setting_repository->findByName($name);
        if (!$setting) { return null; }
        $values = $setting['value'];

        return array_get([$name => $values], $settings_key);
    }


    protected function extractSettingsName($settings_key) {
        $pieces = explode('.', $settings_key);
        return $pieces[0];
    }
}


