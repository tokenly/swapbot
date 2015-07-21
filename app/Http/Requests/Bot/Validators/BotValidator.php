<?php

namespace Swapbot\Http\Requests\Bot\Validators;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Factory;
use LinusU\Bitcoin\AddressValidator;
use Sabberworm\CSS\Parser as CSSParser;
use Sabberworm\CSS\Settings as CSSSettings;
use Sabberworm\CSS\Value\Color as CSSColor;
use Swapbot\Models\User;
use Swapbot\Repositories\ImageRepository;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Strategies\StrategyHelpers;


class BotValidator {

    protected $swaps_required = true;
    protected $income_rules_required = false;

    function __construct(Factory $validator_factory, StrategyFactory $swap_strategy_factory, ImageRepository $image_repository) {
        $this->validator_factory     = $validator_factory;
        $this->swap_strategy_factory = $swap_strategy_factory;
        $this->image_repository      = $image_repository;

        $this->initValidatorRules();
    }

    protected $rules = [];


    public function getRules() {
        return $this->rules;
    }

    public function validate($posted_data, User $user) {
        $validator = $this->buildValidator($posted_data, $user);
        if (!$validator->passes()) {
            throw new ValidationException($validator);        
        }

    }

    protected function buildValidator($posted_data, User $user) {
        $validator = $this->validator_factory->make($posted_data, $this->rules, $messages=[], $customAttributes=[]);
        $validator->after(function ($validator) use ($posted_data, $user) {
            // validate swaps
            $this->validateSwaps(isset($posted_data['swaps']) ? $posted_data['swaps'] : null, $validator);

            // validate blacklist addresses
            $this->validateBlacklistAddresses(isset($posted_data['blacklist_addresses']) ? $posted_data['blacklist_addresses'] : null, $validator);

            // validate income rules
            $this->validateIncomeRules(isset($posted_data['income_rules']) ? $posted_data['income_rules'] : null, $validator);

            // validate images
            $this->validateImageID('background', $user, isset($posted_data['background_image_id']) ? $posted_data['background_image_id'] : null, $validator);
            $this->validateImageID('logo', $user, isset($posted_data['logo_image_id']) ? $posted_data['logo_image_id'] : null, $validator);

            // validate overlay gradient settings
            $this->validateOverlaySettings(isset($posted_data['background_overlay_settings']) ? $posted_data['background_overlay_settings'] : null, $validator);
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

    protected function validateImageID($image_type, User $user, $image_id, $validator) {
        if (strlen($image_id)) {
            if (!is_numeric($image_id)) {
                $validator->errors()->add('image_'.$image_type, "The ID for the {$image_type} was not valid.");
                return;
            }

            $image = $this->image_repository->findByID($image_id);
            if (!$image) {
                $validator->errors()->add('image_'.$image_type, "The ID for the {$image_type} was not found.");
                return;
            }

            if ($image['user_id'] != $user['id']) {
                $validator->errors()->add('image_'.$image_type, "This {$image_type} image could not be associated with this bot.");
                return;

            }
        }
    }

    protected function validateOverlaySettings($overlay_settings, $validator) {
        if (isset($overlay_settings['start']) OR isset($overlay_settings['start'])) {
            $this->validateGradients([
                'start' => isset($overlay_settings['start']) ? $overlay_settings['start'] : null,
                'end'   => isset($overlay_settings['end'])   ? $overlay_settings['end']   : null,
            ], $validator);
        }
        return;
    }

    protected function validateGradients($settings, $validator) {
        $any_errors_found = false;

        $css = '';
        foreach($settings as $name => $gradient) {
            if (!strlen($gradient)) {
                $validator->errors()->add('gradient_'.$name, "This gradient was empty.");
                $any_errors_found = true;
                continue;
            }

            $sanitized = filter_var($gradient, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); // rgba(0,0,0,0.15)
            $sanitized = preg_replace('![^a-z0-9,.()#]!', '', $sanitized);
            if ($sanitized !== $gradient) {
                $validator->errors()->add('gradient_'.$name, "This gradient definition contained illegal characters.");
                $any_errors_found = true;
                continue;
            }

            $css .= '.'.$name.' { color: '.$gradient.'; } ';
        }
        if ($any_errors_found) { return; }

        $css_parser = new CSSParser($css, CSSSettings::create()->beStrict());
        $parsed_css = $css_parser->parse();
        foreach ($parsed_css->getAllRuleSets() as $rule_set) {
            $rules = $rule_set->getRulesAssoc();
            $color_rule_value = $rules['color']->getValue();

            if (!($color_rule_value instanceof CSSColor)) {
                // this is not a valid color
                $validator->errors()->add('gradient_'.$name, "This gradient definition was not a valid color.");
                $any_errors_found = true;
                continue;
            }
        }

    }
    

    protected function initValidatorRules() {
        // abstract method
    }
}
