<?php

namespace Swapbot\Models;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Data\BotStatusDetails;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Statemachines\BotStateMachineFactory;
use Tokenly\CurrencyLib\CurrencyUtil;

class Bot extends APIModel {

    protected $api_attributes = ['id', 'name', 'description', 'swaps', 'blacklist_addresses', 'balances', 'address', 'payment_plan', 'payment_address','return_fee', 'state', ];

    protected $dates = ['balances_updated_at'];

    protected $state_machine = null;

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

    public function setReturnFeeAttribute($return_fee) { $this->attributes['return_fee'] = CurrencyUtil::valueToSatoshis($return_fee); }
    public function getReturnFeeAttribute() { return isset($this->attributes['return_fee']) ? CurrencyUtil::satoshisToValue($this->attributes['return_fee']) : 0; }

    public function setStatusDetailsAttribute($status_details) { $this->attributes['status_details'] = json_encode($this->serializeStatusDetails($status_details)); }
    public function getStatusDetailsAttribute() { return $this->unSerializeStatusDetails($this->attributes['status_details'] ? json_decode($this->attributes['status_details'], true) : []); }



    public function getCreationFee() {
        return 0.005;
    }

    public function getStartingBTCFuel() {
        return 0.01;
    }

    public function getMinimumBTCFuel() {
        return 0.001;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function modifyBalance($asset, $quantity) {
        $balances = $this['balances'];
        if (!isset($balances[$asset])) { $balances[$asset] = 0; }
        $balances[$asset] = $balances[$asset] + $quantity;
        $this['balances'] = $balances;
        return $this;
    }    
    

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function serializeSwaps($swaps) {
        $serialized_swaps = [];
        foreach($swaps as $swap) {
            if (!($swap instanceof SwapConfig)) {
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


    public function serializeStatusDetails($status_details) {
        if ($status_details === null) { return []; }

        if (!($status_details instanceof BotStatusDetails)) {
            throw new Exception("Invalid BotStatusDetail Type", 1);
        }
        return $status_details->serialize();
    }

    public function unSerializeStatusDetails($serialized_status_details) {
        return BotStatusDetails::createFromSerialized($serialized_status_details);
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

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function stateMachine() {
        if (!isset($this->state_machine)) {
            $this->state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($this);
        }
        return $this->state_machine;
    }
}
