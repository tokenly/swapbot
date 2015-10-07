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
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\ImageRepository;
use Swapbot\Swap\Factory\StrategyFactory;
use Swapbot\Swap\Strategies\StrategyHelpers;
use Swapbot\Util\Slug\Slugifier;
use Swapbot\Util\Validator\ValidatorHelper;


class BotValidator {

    protected $swaps_required = true;
    protected $income_rules_required = false;

    function __construct(Factory $validator_factory, StrategyFactory $swap_strategy_factory, ImageRepository $image_repository, BotRepository $bot_repository) {
        $this->validator_factory     = $validator_factory;
        $this->swap_strategy_factory = $swap_strategy_factory;
        $this->image_repository      = $image_repository;
        $this->bot_repository        = $bot_repository;

        $this->initValidatorRules();
    }

    protected $rules = [];

    protected $messages = [
        'url_slug.unique' => 'This Bot URL has already been used.  Please choose another.',
    ];


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
        $validator = $this->validator_factory->make($posted_data, $this->rules, $this->messages, $customAttributes=[]);
        $validator->after(function ($validator) use ($posted_data, $user) {
            $swap_rules = isset($posted_data['swap_rules']) ? $posted_data['swap_rules'] : [];

            $swap_rules_by_id = [];
            foreach($swap_rules as $swap_rule) { $swap_rules_by_id[$swap_rule['uuid']] = true; }

            // validate url slug
            $this->validateURLSlug(isset($posted_data['url_slug']) ? $posted_data['url_slug'] : null, $validator);

            // validate swaps
            $this->validateSwaps(isset($posted_data['swaps']) ? $posted_data['swaps'] : null, $swap_rules_by_id, $validator);

            // validate blacklist addresses
            $this->validateBlacklistAddresses(isset($posted_data['blacklist_addresses']) ? $posted_data['blacklist_addresses'] : null, $validator);

            // validate income rules
            $this->validateIncomeRules(isset($posted_data['income_rules']) ? $posted_data['income_rules'] : null, $validator);

            // validate refund config
            $this->validateRefundConfig(isset($posted_data['refund_config']) ? $posted_data['refund_config'] : null, $validator);

            // validate images
            $this->validateImageID('background', $user, isset($posted_data['background_image_id']) ? $posted_data['background_image_id'] : null, $validator);
            $this->validateImageID('logo', $user, isset($posted_data['logo_image_id']) ? $posted_data['logo_image_id'] : null, $validator);

            // validate overlay gradient settings
            $this->validateOverlaySettings(isset($posted_data['background_overlay_settings']) ? $posted_data['background_overlay_settings'] : null, $validator);

            // validate swap rules
            $this->validateSwapRules($swap_rules, $validator);

        });
        return $validator;
    }

    ////////////////////////////////////////////////////////////////////////
    // Swaps

    protected function validateSwaps($swaps, $swap_rules_by_id, $validator) {
        if ($swaps === null) {
            if ($this->swaps_required) {
                $validator->errors()->add('swaps', "Please specify at least one swap.");
            }
            return;
        }

        if ($swaps) {
            foreach(array_values($swaps) as $offset => $swap) {
                $this->validateSwap($offset, $swap, $swap_rules_by_id, $validator);
            }
        } else {
            // swaps were set but were empty
            $validator->errors()->add('swaps', "Please specify at least one swap.");
        }
    }

    protected function validateSwap($offset, $swap, $swap_rules_by_id, $validator) {
        $strategy_name = isset($swap['strategy']) ? $swap['strategy'] : null;
        $swap_number = $offset + 1;

        // strategy
        if (strlen($strategy_name)) {
            if ($this->isValidStrategyType($strategy_name)) {
                $strategy = $this->swap_strategy_factory->newStrategy($strategy_name);
                $strategy->validateSwap($swap_number, $swap, $validator->errors());

                Log::debug("\$swap['swap_rule_ids']=".json_encode(isset($swap['swap_rule_ids']) ? $swap['swap_rule_ids'] : null, 192));

                // validate any attatched swap rules
                if (isset($swap['swap_rule_ids']) AND is_array($swap['swap_rule_ids'])) {
                    $this->validateAppliedSwapRuleIDs($swap['swap_rule_ids'], $swap_rules_by_id, $strategy, $validator);
                }
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
        } else  if (!ValidatorHelper::isValidAssetName($income_rule['asset'])) {
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


    ////////////////////////////////////////////////////////////////////////
    // refund config
    
    protected function validateRefundConfig($refund_config, $validator) {
        if ($refund_config) {
            if (isset($refund_config['refundAfterBlocks'])) {
                if ($refund_config['refundAfterBlocks'] < 3) {
                    $validator->errors()->add('refund_config', "You must specify 3 or more confirmations for automatic refunds.");
                }
                if ($refund_config['refundAfterBlocks'] > 72) {
                    $validator->errors()->add('refund_config', "You must specify no more than 72 confirmations for automatic refunds.");
                }
            }
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

            if ($image['user_id'] != $user['id'] AND !$user->hasPermission('editBots')) {
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

    protected function validateURLSlug($url_slug, $validator) {
        if (strlen($url_slug) AND !Slugifier::isValidSlug($url_slug)) {
            $validator->errors()->add('url_slug', "This is not a valid URL slug.");
        }

        return;
    }

    ////////////////////////////////////////////////////////////////////////
    // swap rules

    protected function validateAppliedSwapRuleIDs($applied_swap_rule_ids, $swap_rules_by_id, $strategy, $validator) {
        foreach($applied_swap_rule_ids as $applied_swap_rule_id) {
            if (!isset($swap_rules_by_id[$applied_swap_rule_id])) {
                Log::debug("\$applied_swap_rule_id={$applied_swap_rule_id} \$swap_rules_by_id=".json_encode($swap_rules_by_id, 192));
                $validator->errors()->add('applied_swap_rule_id', "Please specify a valid id for this swap.");
                continue;
            }

            $strategy->validateSwapRuleConfig($swap_rules_by_id[$applied_swap_rule_id], $validator->errors());
        }
    }
    
    protected function validateSwapRules($swap_rules, $validator) {
        if ($swap_rules AND is_array($swap_rules)) {
            foreach(array_values($swap_rules) as $offset => $swap_rule) {
                $this->validateSwapRule($offset, $swap_rule, $validator);
            }
        }
    }

    protected function validateSwapRule($offset, $swap_rule, $validator) {
        Log::debug("\$swap_rule=".json_encode($swap_rule, 192));
        $swap_rule_number = $offset + 1;

        // name required
        if (!strlen($swap_rule['name'])) {
            $validator->errors()->add('name_'.$swap_rule_number, "Please specify a name for Swap Rule #{$swap_rule_number}");
        }
        // ruleType required
        if (!strlen($swap_rule['ruleType'])) {
            $validator->errors()->add('ruleType_'.$swap_rule_number, "Please specify a type for Swap Rule #{$swap_rule_number}");
        }
        // uuid required
        if (!strlen($swap_rule['uuid'])) {
            $validator->errors()->add('uuid_'.$swap_rule_number, "Please specify a UUID for Swap Rule #{$swap_rule_number}");
        }

        // discounts
        if ($swap_rule['ruleType'] == 'bulkDiscount') {
            if ($swap_rule['discounts']) {
                // validate each discount
                foreach ($swap_rule['discounts'] as $discount_offset => $discount) {
                    // validate each discount
                    $this->validateDiscount($discount, $discount_offset, $swap_rule_number, $validator);
                }
            } else {
                $validator->errors()->add('discounts_'.$swap_rule_number, "Please specify discounts for Swap Rule #{$swap_rule_number}");
            }
        }
    }

    protected function validateDiscount($discount, $discount_offset, $swap_rule_number, $validator) {
        $discount_number = $discount_offset + 1;
        $identifier = $swap_rule_number.'_'.$discount_number;
        $text_identifier = "Discount {$discount_number} of Swap Rule #{$swap_rule_number}";
        if (strlen($discount['moq'])) {
            if (!ValidatorHelper::isValidQuantityOrZero($discount['moq'])) {
                $validator->errors()->add('moq_'.$identifier, "The minimum order for {$text_identifier} was invalid");
            }
        } else {
            $validator->errors()->add('moq_'.$identifier, "Please specify a minimum order for {$text_identifier}");
        }

        if (strlen($discount['pct'])) {
            if (!ValidatorHelper::isValidPercentage($discount['pct'])) {
                $validator->errors()->add('pct_'.$identifier, "The percentage for {$text_identifier} was invalid");
            }
        } else {
            $validator->errors()->add('pct_'.$identifier, "Please specify a percentage for {$text_identifier}");
        }

    }

    // 'moq' => '10',
    // 'pct' => '0.1',


    protected function initValidatorRules() {
        // abstract method
    }
}
