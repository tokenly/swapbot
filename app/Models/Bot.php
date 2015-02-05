<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;

class Bot extends APIModel {

    protected $api_attributes = ['id', 'name', 'description', 'swaps', 'blacklist_addresses', 'balances', 'address', 'active', ];

    protected $dates = ['balances_updated_at'];

    public function buildSwapID($swap) {
        return $swap['in'].':'.$swap['out'];
    }

    public function setSwapsAttribute($swaps) { $this->attributes['swaps'] = json_encode($this->serializeSwaps($swaps)); }
    public function getSwapsAttribute() { return $this->deSerializeSwaps(json_decode($this->attributes['swaps'], true)); }

    public function setActiveAttribute($active) { $this->attributes['active'] = $active ? 1 : 0; }
    public function getActiveAttribute() { return !!$this->attributes['active']; }

    public function setBalancesAttribute($balances) { $this->attributes['balances'] = json_encode($balances); }
    public function getBalancesAttribute() { return isset($this->attributes['balances']) ? json_decode($this->attributes['balances'], true) : []; }

    public function setBlacklistAddressesAttribute($blacklist_addresses) { $this->attributes['blacklist_addresses'] = json_encode($this->serializeBlacklistAddresses($blacklist_addresses)); }
    public function getBlacklistAddressesAttribute() { return $this->deSerializeBlacklistAddresses(json_decode($this->attributes['blacklist_addresses'], true)); }

    public function serializeSwaps($swaps) {
        $serialized_swaps = [];
        foreach($swaps as $asset) {
            $serialized_swaps[] = [$asset['in'], $asset['out'], $asset['rate']];
        }
        return $serialized_swaps;
    }

    public function deSerializeSwaps($serialized_swaps) {
        $deserialized_swaps = [];
        foreach($serialized_swaps as $asset) {
            $deserialized_swaps[] = [
                'in'   => $asset[0],
                'out'  => $asset[1],
                'rate' => $asset[2],
            ];
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

    public function deSerializeBlacklistAddresses($serialized_blacklist_addresses) {
        if (!is_array($serialized_blacklist_addresses)) { return []; }
        return $serialized_blacklist_addresses;
    }
}
