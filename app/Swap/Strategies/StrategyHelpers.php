<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;
use Swapbot\Util\Validator\ValidatorHelper;


class StrategyHelpers {

    public static function validateAssetName($asset, $asset_description_type, $swap_number, $error_key, MessageBag $errors) {
        $assets_are_valid = true;
        if (strlen($asset)) {
            if (!ValidatorHelper::isValidAssetName($asset)) {
                $assets_are_valid = false;
                $errors->add($error_key, "The {$asset_description_type} asset name for swap #{$swap_number} was not valid.");
            }
        } else {
            $errors->add($error_key, "Please specify an asset to {$asset_description_type} for swap #{$swap_number}");
        }

        return $assets_are_valid;
    }

    public static function validateQuantity($quantity, $quantity_description_type, $swap_number, $error_key, MessageBag $errors) {
        if (strlen($quantity)) {
            if (!ValidatorHelper::isValidQuantity($quantity)) {
                $errors->add($error_key, "The {$quantity_description_type} quantity for swap #{$swap_number} was not valid.");
            }
        } else {
            $errors->add($error_key, "Please specify a {$quantity_description_type} quantity for swap #{$swap_number}");
        }
    }


    public static function isValidRate($rate) {
        $rate = floatval($rate);
        if ($rate <= 0) { return false; }

        return true;
    }

    public static function isValidPrice($price) {
        return self::isValidRate($price);
    }

    public static function isValidCost($rate) {
        $rate = floatval($rate);

        // minimum USD pricing
        if ($rate < 0.00000001) { return false; }

        return true;
    }


}
