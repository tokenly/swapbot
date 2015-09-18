<?php

namespace Swapbot\Util\Slug;

use Exception;

/*
* Slugifier
*/
class Slugifier {

    const MIN_LENGTH = 8;
    const MAX_LENGTH = 80;

    public static function isValidSlug($slug) {
        $len = strlen($slug);
        return (preg_match('!^[a-z0-9-]+$!', $slug) AND $len >= self::MIN_LENGTH AND $len <= self::MAX_LENGTH);
    }

}

