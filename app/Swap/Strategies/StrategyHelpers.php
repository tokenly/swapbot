<?php

namespace Swapbot\Swap\Strategies;

use Illuminate\Support\MessageBag;


class StrategyHelpers {

    public static function validateAssetName($asset, $asset_description_type, $swap_number, $error_key, MessageBag $errors) {
        $assets_are_valid = true;
        if (strlen($asset)) {
            if (!self::isValidAssetName($asset)) {
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
            if (!self::isValidQuantity($quantity)) {
                $errors->add($error_key, "The {$quantity_description_type} quantity for swap #{$swap_number} was not valid.");
            }
        } else {
            $errors->add($error_key, "Please specify a {$quantity_description_type} quantity for swap #{$swap_number}");
        }
    }

    public static function isValidAssetName($name) {
        if ($name === 'BTC') { return true; }
        if ($name === 'XCP') { return true; }
        if (!preg_match('!^[A-Z]+$!', $name)) { return false; }
        if (strlen($name) < 4) { return false; }
        if (substr($name, 0, 1) == 'A') { return false; }
        if (strlen($name) > 12) { return false; }

        return true;
    }

    public static function isValidRate($rate) {
        $rate = floatval($rate);
        if ($rate <= 0) { return false; }

        return true;
    }

    public static function isValidCost($rate) {
        $rate = floatval($rate);
        if ($rate < 0.01) { return false; }

        return true;
    }

    public static function isValidQuantityOrZero($quantity) {
        return self::isValidQuantity($quantity, true);
    }

    public static function isValidQuantity($quantity, $allow_zero=false) {
        $quantity = floatval($quantity);
        if ($allow_zero) {
            if ($quantity < 0) { return false; }
        } else {
            if ($quantity <= 0) { return false; }
        }

        return true;
    }


}
