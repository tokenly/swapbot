<?php

namespace Swapbot\Util\Validator;

class ValidatorHelper {


    public static function isValidAssetName($name) {
        if ($name === 'BTC') { return true; }
        if ($name === 'XCP') { return true; }

        // check free asset names
        if (substr($name, 0, 1) == 'A') { return self::isValidFreeAssetName($name); }

        if (!preg_match('!^[A-Z]+$!', $name)) { return false; }
        if (strlen($name) < 4) { return false; }

        return true;
    }

    // allow integers between 26^12 + 1 and 256^8 (inclusive), prefixed with 'A'
    public static function isValidFreeAssetName($name) {
        if (substr($name, 0, 1) != 'A') { return false; }

        $number_string = substr($name, 1);
        if (!preg_match('!^\\d+$!', $number_string)) { return false; }
        if (bccomp($number_string, "95428956661682201") < 0) { return false; }
        if (bccomp($number_string, "18446744073709600000") > 0) { return false; }

        return true;
    }

    public static function isValidPercentage($quantity, $allow_zero=false, $allow_more_than_one=false) {

        if (self::isValidQuantity($quantity, $allow_zero)) {
            if ($allow_more_than_one OR $quantity <= 1.0) {
                return true;
            }
        }

        return false;
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
