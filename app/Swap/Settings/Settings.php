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
        if (!$setting) {
            return $this->getDefault($settings_key);
        }

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

    // ------------------------------------------------------------------------
    
    protected function getDefault($settings_key) {
        $method_name = "getDefault_".preg_replace('![^a-z0-9]+!i','',$settings_key);
        if (method_exists($this, $method_name)) {
            return $this->{$method_name}();
        }

        return null;
    }

    protected function extractSettingsName($settings_key) {
        $pieces = explode('.', $settings_key);
        return $pieces[0];
    }

    // ------------------------------------------------------------------------
    
    protected function getDefault_pricedTokens() {
        $raw_string = env('DEFAULT_PRICED_TOKENS');
        if (strlen($raw_string)) {
            return json_decode($raw_string, true);
        }
        return [];
    }
}


