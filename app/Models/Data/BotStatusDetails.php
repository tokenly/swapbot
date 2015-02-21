<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class BotStatusDetails extends ArrayObject implements APISerializeable {

    function __construct($data=[]) {
        parent::__construct($data);
    }

    public static function createFromSerialized($data) {
        $status_details = new BotStatusDetails();
        $status_details->unSerialize($data);
        return $status_details;
    }

    public function unSerialize($data) {
        // $this['creationFeeIsPaid'] = isset($data['creationFeeIsPaid']) ? !!$data['creationFeeIsPaid'] : false;
        return $this;
    }

    public function serialize() {
        return [
            // 'creationFeeIsPaid' => isset($this['creationFeeIsPaid']) ? $this['creationFeeIsPaid'] : false,
        ];
    }

    public function serializeForAPI() { return $this->serialize(); }




}
