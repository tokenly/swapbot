<?php

namespace Swapbot\Models;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Data\SwapConfig;

class Bot extends APIModel {

    protected $api_attributes = ['id', 'name', 'description', 'swaps', 'blacklist_addresses', 'balances', 'address', 'active', ];

    protected $dates = ['balances_updated_at'];

    public function buildSwapID($swap) {
        return $swap['in'].':'.$swap['out'];
    }

    public function setSwapsAttribute($swaps) { $this->attributes['swaps'] = json_encode($this->serializeSwaps($swaps)); }
    public function getSwapsAttribute() { return $this->unSerializeSwaps(json_decode($this->attributes['swaps'], true)); }

    public function setActiveAttribute($active) { $this->attributes['active'] = $active ? 1 : 0; }
    public function getActiveAttribute() { return !!$this->attributes['active']; }

    public function setBalancesAttribute($balances) { $this->attributes['balances'] = json_encode($balances); }
    public function getBalancesAttribute() { return isset($this->attributes['balances']) ? json_decode($this->attributes['balances'], true) : []; }

    public function setBlacklistAddressesAttribute($blacklist_addresses) { $this->attributes['blacklist_addresses'] = json_encode($this->serializeBlacklistAddresses($blacklist_addresses)); }
    public function getBlacklistAddressesAttribute() { return $this->unSerializeBlacklistAddresses(json_decode($this->attributes['blacklist_addresses'], true)); }

    public function serializeSwaps($swaps) {
        $serialized_swaps = [];
        foreach($swaps as $swap) {
            if (!($swap instanceof SwapConfig)) {
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                throw new Exception("Invalid Swap Type", 1);
            }
            $serialized_swaps[] = $swap->serialize();
        }
        return $serialized_swaps;
    }

    public function unSerializeSwaps($serialized_swaps) {
        $deserialized_swaps = [];
        foreach($serialized_swaps as $serialized_swap_data) {
            $deserialized_swaps[] = SwapConfig::createFromSerialized($serialized_swap_data);
        }
        return $deserialized_swaps;
    }

    public function serializeBlacklistAddresses($blacklist_addresses) {
        $serialized_blacklist_addresses = [];
        if (is_array($blacklist_addresses)) {
            foreach($blacklist_addresses as $address) {
                if (strlen($address)) {
                    $serialized_blacklist_addresses[] = $address;
                }
            }
        }
        return $serialized_blacklist_addresses;
    }

    public function unSerializeBlacklistAddresses($serialized_blacklist_addresses) {
        if (!is_array($serialized_blacklist_addresses)) { return []; }
        return $serialized_blacklist_addresses;
    }
}
