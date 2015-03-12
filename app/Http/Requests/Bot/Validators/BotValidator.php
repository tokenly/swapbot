<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use LinusU\Bitcoin\AddressValidator;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Strategies\StrategyHelpers;


class BotValidator {

    protected $swaps_required = true;
    protected $income_rules_required = false;

    function __construct(Factory $validator_factory, StrategyFactory $swap_strategy_factory) {
        $this->validator_factory     = $validator_factory;
        $this->swap_strategy_factory = $swap_strategy_factory;

        $this->initValidatorRules();
    }

    protected $rules = [];


    public function getRules() {
        return $this->rules;
    }

    public function validate($posted_data) {
        $validator = $this->buildValidator($posted_data);
        if (!$validator->passes()) {
            throw new ValidationException($validator);        
        }

    }

    protected function buildValidator($posted_data) {
        $validator = $this->validator_factory->make($posted_data, $this->rules, $messages=[], $customAttributes=[]);
        $validator->after(function ($validator) use ($posted_data) {
            // validate swaps
            $this->validateSwaps(isset($posted_data['swaps']) ? $posted_data['swaps'] : null, $validator);
            // validate blacklist addresses
            $this->validateBlacklistAddresses(isset($posted_data['blacklist_addresses']) ? $posted_data['blacklist_addresses'] : null, $validator);
            // validate income rules
            $this->validateIncomeRules(isset($posted_data['income_rules']) ? $posted_data['income_rules'] : null, $validator);
        });
        return $validator;
    }

    ////////////////////////////////////////////////////////////////////////
    // Swaps

    protected function validateSwaps($swaps, $validator) {
        if ($swaps === null) {
            if ($this->swaps_required) {
                $validator->errors()->add('swaps', "Please specify at least one swap.");
            }
            return;
        }

        if ($swaps) {
            foreach(array_values($swaps) as $offset => $swap) {
                $this->validateSwap($offset, $swap, $validator);
            }
        } else {
            // swaps were set but were empty
            $validator->errors()->add('swaps', "Please specify at least one swap.");
        }
    }

    protected function validateSwap($offset, $swap, $validator) {
        $strategy_name = isset($swap['strategy']) ? $swap['strategy'] : null;
        $swap_number = $offset + 1;

        // strategy
        if (strlen($strategy_name)) {
            if ($this->isValidStrategyType($strategy_name)) {
                $this->swap_strategy_factory->newStrategy($strategy_name)->validateSwap($swap_number, $swap, $validator->errors());
            } else {
                $validator->errors()->add('strategy', "The strategy for swap #{$swap_number} was not valid.");
            }
        } else {
            $validator->errors()->add('strategy', "Please specify a swap strategy for swap #{$swap_number}");
        }
    }

    protected function isValidStrategyType($strategy) {
        return $this->swap_strategy_factory->isValidStrategyType($strategy);
    }

    ////////////////////////////////////////////////////////////////////////
    // Blacklist Addresses

    protected function validateBlacklistAddresses($blacklist_addresses, $validator) {
        if ($blacklist_addresses) {
            foreach(array_values($blacklist_addresses) as $offset => $blacklist_address) {
                if (strlen($blacklist_address) AND !AddressValidator::isValid($blacklist_address)) {
                    $validator->errors()->add('blacklist_addresses', "Blacklist address {$blacklist_address} was not a valid bitcoin address.");
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////
    // income rules
    
    protected function validateIncomeRules($income_rules, $validator) {
        // if ($income_rules === null) {
        //     if ($this->income_rules_required) {
        //         $validator->errors()->add('income_rules', "Please specify at least one income rule.");
        //     }
        //     return;
        // }

        if ($income_rules) {
            foreach(array_values($income_rules) as $offset => $income_rule) {
                $this->validateIncomeRule($offset, $income_rule, $validator);
            }
        } else {
            if ($this->income_rules_required) {
                // income_rules were required but were empty
                $validator->errors()->add('income_rules', "Please specify at least one income rule.");
            }
        }
    }

    protected function validateIncomeRule($offset, $income_rule, $validator) {
        $income_rule_number = $offset + 1;

        // asset
        if (!strlen($income_rule['asset'])) {
            $validator->errors()->add('asset_'.$income_rule_number, "Please specify an asset for Income Rule #{$income_rule_number}");
        } else  if (!StrategyHelpers::isValidAssetName($income_rule['asset'])) {
            $validator->errors()->add('asset_'.$income_rule_number, "The asset name for Income Rule #{$income_rule_number} was not valid");
        }

        // minThreshold
        if (!strlen($income_rule['minThreshold']) OR $income_rule['minThreshold'] < 0) {
            $validator->errors()->add('minThreshold_'.$income_rule_number, "Please specify a minimum threshold for Income Rule #{$income_rule_number}");
        }

        // paymentAmount
        if (!strlen($income_rule['paymentAmount']) OR $income_rule['paymentAmount'] < 0) {
            $validator->errors()->add('paymentAmount_'.$income_rule_number, "Please specify a payment amount for Income Rule #{$income_rule_number}");
        }

        // address
        if (!strlen($income_rule['address'])) {
            $validator->errors()->add('address_'.$income_rule_number, "Please specify a payment address for Income Rule #{$income_rule_number}");
        } else if (!AddressValidator::isValid($income_rule['address'])) {
            $validator->errors()->add('address', "The payment address {$income_rule['address']} was not a valid bitcoin address.");
        }


    }


    

    protected function initValidatorRules() {
        // abstract method
    }
}
