<?php

namespace Swapbot\Http\Requests\Bot\Transformers;

use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\IncomeRuleConfig;
use Swapbot\Models\Data\RefundConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapRuleConfig;
use Swapbot\Repositories\ImageRepository;

class BotTransformer {

    public function __construct(ImageRepository $image_repository) {
        $this->image_repository = $image_repository;
    }

    public function santizeAttributes($attributes, $rules) {

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            // $out[$field_name] = isset($attributes[$field_name]) ? $attributes[$field_name] : null;
            $camel_field_name = camel_case($field_name);
            if (isset($attributes[$camel_field_name])) {
                // try camel first
                $out[$field_name] = $attributes[$camel_field_name];
            } else if (isset($attributes[$field_name])) {
                // fall back to snake
                $out[$field_name] = $attributes[$field_name];
            }
        }

        // santize swaps
        if (isset($attributes['swaps'])) {
            $out['swaps'] = $this->sanitizeSwaps($attributes['swaps']);
        }

        // santize blacklistAddresses
        if (isset($attributes['blacklistAddresses'])) {
            $out['blacklist_addresses'] = $this->sanitizeBlacklistAddresses($attributes['blacklistAddresses']);
        }

        // santize whitelistAddresses
        if (isset($attributes['whitelistAddresses'])) {
            $out['whitelist_addresses'] = $this->sanitizeWhitelistAddresses($attributes['whitelistAddresses']);
        }

        // santize incomeRules
        if (isset($attributes['incomeRules'])) {
            $out['income_rules'] = $this->sanitizeIncomeRules($attributes['incomeRules']);
        }

        // santize refundConfig
        if (isset($attributes['refundConfig'])) {
            $out['refund_config'] = $this->sanitizeRefundConfig($attributes['refundConfig']);
        }

        // lookup background image id
        if (isset($attributes['backgroundImageId'])) {
            $out['background_image_id'] = $this->sanitizeImageID($attributes['backgroundImageId']);
        }
        // lookup logo image id
        if (isset($attributes['logoImageId'])) {
            $out['logo_image_id'] = $this->sanitizeImageID($attributes['logoImageId']);
        }

        // sanitize background_overlay_settings
        if (isset($attributes['backgroundOverlaySettings'])) {
            $out['background_overlay_settings'] = $this->sanitizeOverlaySettings($attributes['backgroundOverlaySettings']);
        }


        // santize swapRules
        if (isset($attributes['swapRules'])) {
            $out['swap_rules'] = $this->sanitizeSwapRules($attributes['swapRules']);
        }

        return $out;
    }

    ////////////////////////////////////////////////////////////////////////
    // Swaps

    protected function sanitizeSwaps($swaps) {
        $swaps_out = [];

        if ($swaps) {
            foreach(array_values($swaps) as $offset => $swap) {
                $swaps_out[] = $this->sanitizeSwap($swap);
            }
        }

        return $swaps_out;
    }

    protected function sanitizeSwap($swap) {
        return SwapConfig::createFromSerialized($swap);
    }

    ////////////////////////////////////////////////////////////////////////
    // Blacklist Addresses

    protected function sanitizeBlacklistAddresses($blacklist_addresses) {
        $blacklist_addresses_out = [];

        if ($blacklist_addresses) {
            foreach(array_values($blacklist_addresses) as $offset => $blacklist_address) {
                if (strlen($blacklist_address)) {
                    $blacklist_addresses_out[] = $blacklist_address;
                }
            }
        }

        return $blacklist_addresses_out;
    }

    ////////////////////////////////////////////////////////////////////////
    // Whitelist Addresses

    protected function sanitizeWhitelistAddresses($whitelist_addresses) {
        $whitelist_addresses_out = [];

        if ($whitelist_addresses) {
            foreach(array_values($whitelist_addresses) as $offset => $whitelist_address) {
                if (strlen($whitelist_address)) {
                    $whitelist_addresses_out[] = $whitelist_address;
                }
            }
        }

        return $whitelist_addresses_out;
    }

    ////////////////////////////////////////////////////////////////////////
    // Income Rules

    protected function sanitizeIncomeRules($income_rules) {
        $income_rules_out = [];

        if ($income_rules) {
            foreach(array_values($income_rules) as $offset => $income_rule_vars) {
                $income_rule = $this->sanitizeIncomeRule($income_rule_vars);
                
                if ($income_rule) {
                    $income_rules_out[] = $income_rule;
                }
            }
        }

        return $income_rules_out;
    }

    protected function sanitizeIncomeRule($income_rule) {
        $config = IncomeRuleConfig::createFromSerialized($income_rule);
        if ($config->isEmpty()) { return null; }
        return $config;
    }


    ////////////////////////////////////////////////////////////////////////
    // Refund Config

    protected function sanitizeRefundConfig($refund_config) {
        return RefundConfig::createFromSerialized($refund_config);
    }



    ////////////////////////////////////////////////////////////////////////
    // Image ID

    protected function sanitizeImageID($image_uuid) {
        Log::debug("sanitizeImageID \$image_uuid=$image_uuid");
        if (strlen($image_uuid)) {
            $image = $this->image_repository->findByUuid($image_uuid);
            // Log::debug("sanitizeImageID findByUuid \$image=".json_encode($image, 192));
            if ($image) {
                return $image['id'];
            }

            // return the uuid which will fail validation
            return $image_uuid;
        }

        return null;
    }

    protected function sanitizeOverlaySettings($background_overlay_settings) {
        $out = [];

        if (isset($background_overlay_settings['start']) AND isset($background_overlay_settings['end'])) {
            $out['start'] = $background_overlay_settings['start'];
            $out['end'] = $background_overlay_settings['end'];
        }

        return $out;
    }

    ////////////////////////////////////////////////////////////////////////
    // Swap Rules

    protected function sanitizeSwapRules($swap_rules) {
        $swap_rules_out = [];

        if ($swap_rules) {
            foreach(array_values($swap_rules) as $offset => $swap_rule_vars) {
                $swap_rule = $this->sanitizeSwapRule($swap_rule_vars);
                
                if ($swap_rule) {
                    $swap_rules_out[] = $swap_rule;
                }
            }
        }

        return $swap_rules_out;
    }

    protected function sanitizeSwapRule($swap_rule) {
        $config = SwapRuleConfig::createFromSerialized($swap_rule);
        if ($config->isEmpty()) { return null; }
        return $config;
    }


}
