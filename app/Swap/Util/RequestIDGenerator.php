<?php

namespace Swapbot\Swap\Util;

use Exception;

/*
* RequestIDGenerator
*/
class RequestIDGenerator {

    public static function generateSendHash($prefix, $destination, $quantity, $asset) {
        if (is_array($prefix)) { $prefix = implode(',', $prefix); }
        if (!strlen($prefix))      { throw new Exception("prefix was empty", 1); }
        if (!strlen($destination)) { throw new Exception("destination was empty", 1); }
        if (!strlen($quantity))    { throw new Exception("quantity was empty", 1); }
        if (!strlen($asset))       { throw new Exception("asset was empty", 1); }

        $text_to_be_hashed = $prefix.','.$destination.','.$quantity.','.$asset;
        return md5($text_to_be_hashed);
    }

}

