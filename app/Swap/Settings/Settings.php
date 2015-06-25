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

    public function put($name, $settings_value) {
        $this->setting_repository->createOrUpdate($name, $settings_value);
    }

    public function delete($name) {
        $settings = $this->setting_repository->findByName($name);
        if ($settings) {
            $this->setting_repository->delete($settings);
            return $settings;
        }

        return false;
    }

    protected function extractSettingsName($settings_key) {
        $pieces = explode('.', $settings_key);
        return $pieces[0];
    }
}


