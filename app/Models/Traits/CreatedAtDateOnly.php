<?php

namespace Swapbot\Models\Traits;

trait CreatedAtDateOnly {


    public function getDates() {
        return [static::CREATED_AT];
    }

    public function setUpdatedAt($value) { }

}
