<?php

class TokenpassCalledAPIMethods extends ArrayObject {

    function __construct() {
        $data = [];
        $data['calls'] = [];

        return parent::__construct($data);
    }


    public function recordCall($method, $args=[]) {
        $this['calls'][] = [
            'method' => $method,
            'args'   => $args,
        ];
    }

    public function getCalls() {
        return $this['calls'];
    }

    public function getCall($offset) {
        return isset($this['calls'][$offset]) ? $this['calls'][$offset] : null;
    }

    public function getCallArgument($call_offset, $arg_offset) {
        $call_details = $this->getCall($call_offset);
        if (!$call_details) { return null; }
        if (isset($call_details['args'][$arg_offset])) {
            return $call_details['args'][$arg_offset];
        }
        return null;
    }

    public function getCallMethod($call_offset) {
        $call_details = $this->getCall($call_offset);
        if (!$call_details) { return null; }
        return $call_details['method'];
    }

}
