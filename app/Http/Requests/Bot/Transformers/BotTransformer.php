<?php

namespace Swapbot\Http\Requests\Bot\Transformers;

use Illuminate\Support\Facades\Log;
use Swapbot\Models\Data\SwapConfig;

class BotTransformer {

    public function santizeAttributes($attributes, $rules) {
        // Log::debug('$attributes'.json_encode($attributes, 192));

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            // $out[$field_name] = isset($attributes[$field_name]) ? $attributes[$field_name] : null;
            if (isset($attributes[$field_name])) {
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

        return $out;
    }

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


}
