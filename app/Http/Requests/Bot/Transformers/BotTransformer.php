<?php

namespace Swapbot\Http\Requests\Bot\Transformers;

use Illuminate\Support\Facades\Log;

class BotTransformer {

    public function santizeAttributes($attributes, $rules) {
        Log::debug('$attributes'.json_encode($attributes, 192));

        $out = [];
        foreach (array_keys($rules) as $field_name) {
            $out[$field_name] = isset($attributes[$field_name]) ? $attributes[$field_name] : null;
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

        return [
            'in'   => isset($swap['in']) ? $swap['in'] : null,
            'out'  => isset($swap['out']) ? $swap['out'] : null,
            'rate' => isset($swap['rate']) ? $swap['rate'] : null,
        ];

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
