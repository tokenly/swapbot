<?php

namespace Swapbot\Swap\Exception;
use Exception;
use Swapbot\Models\BotEvent;

class SwapStrategyException extends Exception {

    protected $error_name = '';
    protected $error_data = [];
    protected $error_level = null;

    function __construct($message='', $code=0, Exception $previous=null) {
        parent::__construct();

        $this->error_level = BotEvent::LEVEL_WARNING;
    }

    public function setErrorName($error_name) { $this->error_name = $error_name; }
    public function getErrorName() { return $this->error_name; }

    public function setErrorData($error_data) { $this->error_data = $error_data; }
    public function getErrorData() { return $this->error_data; }

    public function setErrorLevel($error_level) { $this->error_level = $error_level; }
    public function getErrorLevel() { return $this->error_level; }


}
