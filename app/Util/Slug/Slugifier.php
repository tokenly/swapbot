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

    public static function slugify($str) {
        $slug = strtolower(trim($str));
        $slug = preg_replace("/[^a-z0-9[:space:]\/s-]/", "", $slug);
        $slug = preg_replace("/(-| |\/)+/", "-", $slug);
        $slug = preg_replace("/^-/", "", $slug);
        $slug = preg_replace("/-$/", "", $slug);

        return $slug;
    }

}

