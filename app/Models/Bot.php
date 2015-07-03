<?php

namespace Swapbot\Models;

use Exception;
use GrahamCampbell\Markdown\Facades\Markdown;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Billing\PaymentPlan\PaymentPlan;
use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Data\BotState;
use Swapbot\Models\Data\BotStatusDetails;
use Swapbot\Models\Data\IncomeRuleConfig;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Statemachines\BotStateMachineFactory;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class Bot extends APIModel {

    protected $api_attributes = ['id', 'name', 'username', 'description', 'description_html', 'swaps', 'blacklist_addresses', 'balances', 'address', 'payment_plan', 'payment_address','return_fee', 'state', 'income_rules', 'confirmations_required', 'hash', ];
    protected $api_attributes_public = ['id', 'name', 'username', 'description', 'description_html', 'swaps', 'balances', 'address', 'return_fee', 'state', 'confirmations_required', 'hash', ];

    protected $dates = ['balances_updated_at'];

    protected $state_machine        = null;
    protected $payment_plan_details = null;
    protected $payment_plan_object  = null;

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

    public function setIncomeRulesAttribute($income_rules) { $this->attributes['income_rules'] = json_encode($this->serializeIncomeRules($income_rules)); }
    public function getIncomeRulesAttribute() { return $this->unSerializeIncomeRules(json_decode($this->attributes['income_rules'], true)); }
    
    public function getUsernameAttribute() {
        // get username
        return $this->user['username'];
    }

    public function getDescriptionHtmlAttribute() {
        return Markdown::convertToHtml(strip_tags(
            $this->attributes['description'],
            '<hr><hr/><li><li/><ol><ol/><caption><caption/><col><col/><p><p/><colgroup><colgroup/><pre><pre/><dd><dd/><div><div/><dl><dl/><table><table/><td><td/><dt><dt/><tbody><tbody/><tfoot><tfoot/><th><th/><thead><thead/><tr><tr/><ul><ul/><h1><h1/><h2><h2/><h3><h3/><h4><h4/><h5><h5/><h6><h6/>'
        ));
    }

    public function getRobohashURL() {
        return str_replace('%%HASH%%', $this['hash'], Config::get('swapbot.robohash_url'));
    }

    public function getPublicBotURL() {
        return Config::get('swapbot.site_host')."/public/{$this['username']}/{$this['uuid']}";
    }

    public function isActive($state=null) {
        $state = ($state === null ? $this['state'] : $state);

        switch ($state) {
            case BotState::ACTIVE:
                return true;
        }

        return false;

    }


    public function getStartingBTCFuel() {
        return 0.001;
    }

    public function getMinimumBTCFuel() {
        return 0.0002;
    }

    public function getBalance($asset) {
        $balances = $this['balances'];
        if (isset($balances[$asset])) {
            return $balances[$asset];
        }
        return 0;
    }

    public function user() {
        return $this->belongsTo('Swapbot\Models\User');
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

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////


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

    public function serializeIncomeRules($income_rules) {
        $serialized_income_rules = [];
        foreach($income_rules as $income_rule) {
            if (!($income_rule instanceof IncomeRuleConfig)) {
                throw new Exception("Invalid IncomeRule Type of ".(is_object($income_rule) ? get_class($income_rule) : (is_array($income_rule) ? 'array' : 'unknown')), 1);
            }
            $serialized_income_rules[] = $income_rule->serialize();
        }
        return $serialized_income_rules;
    }

    public function unSerializeIncomeRules($serialized_income_rules) {
        $deserialized_income_rules = [];
        foreach($serialized_income_rules as $serialized_income_rule_data) {
            $deserialized_income_rules[] = IncomeRuleConfig::createFromSerialized($serialized_income_rule_data);
        }
        return $deserialized_income_rules;
    }

    public function getAllIncomeForwardingAddresses() {
        $addresses_map = [];
        foreach ($this['income_rules'] as $income_rule) {
            $addresses_map[$income_rule['address']] = true;
        }
        return array_keys($addresses_map);
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function stateMachine() {
        if (!isset($this->state_machine)) {
            $this->state_machine = app('Swapbot\Statemachines\BotStateMachineFactory')->buildStateMachineFromBot($this);
        }
        return $this->state_machine;
    }


    public function paymentPlanDetails() {
        if (!isset($this->payment_plan_details)) {
            $plans = app('Swapbot\Billing\PaymentPlans')->allPaymentPlans();
            $this->payment_plan_details = isset($plans[$this['payment_plan']]) ? $plans[$this['payment_plan']] : [];
        }
        return $this->payment_plan_details;
    }

    public function getPaymentPlan() {
        if (!isset($this->payment_plan_object)) {
            $this->payment_plan_object = new PaymentPlan($this->paymentPlanDetails());
        }
        return $this->payment_plan_object;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function buildHash() {
        // these fields can change
        //  'blacklist_addresses', 'balances', 'payment_plan', 'payment_address', 'state', 'hash'
        
        // these fields will affect the robohash
        $fields = ['uuid', 'name', 'username', 'description', 'swaps', 'address', 'return_fee', 'income_rules', 'confirmations_required', ];

        $source = "";
        foreach($fields as $field) {
            $value = $this[$field];

            if (is_array($value)) {
                $text = json_encode($value);
            } else {
                $text = $value;
            }

            $source .= $field.":".$text."|";
        }
        $source = substr($source, 0, strlen($source) - 1);

        return hash("sha256", $source);
    }
    
}
