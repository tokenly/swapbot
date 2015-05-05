<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;

class Swap extends APIModel {

    protected $api_attributes = ['id', 'txid', 'state', 'receipt', 'address', 'updated_at', ];


    protected $state_machine        = null;

    protected $casts = [
        'definition' => 'json',
        'receipt'    => 'json',
    ];

    public function getAddressAttribute() {
        $xchain_notification = $this->transaction['xchain_notification'];
        return $xchain_notification['sources'][0];
    }
    public function getTxidAttribute() {
        $xchain_notification = $this->transaction['xchain_notification'];
        return $xchain_notification['txid'];
    }
    public function getInQtyAttribute() {
        $xchain_notification = $this->transaction['xchain_notification'];
        return $xchain_notification['quantity'];
    }
    public function getInAssetAttribute() {
        $xchain_notification = $this->transaction['xchain_notification'];
        return $xchain_notification['asset'];
    }

    public function transaction() {
        return $this->belongsTo('Swapbot\Models\Transaction');
    }

    public function bot() {
        return $this->belongsTo('Swapbot\Models\Bot');
    }


    public function getSwapConfig() {
        return SwapConfig::createFromSerialized($this['definition']);
    }

    // pending swaps are those that have not been processed yet
    public function isPending() {
        return in_array($this['state'], SwapState::allPendingStates());
    }

    public function isReady() {
        return ($this['state'] == SwapState::READY);
    }
    public function isConfirming() {
        return ($this['state'] == SwapState::CONFIRMING);
    }
    public function isComplete($state=null) {
        return (($state === null ? $this['state'] : $state) == SwapState::COMPLETE);
    }
    public function isError($state=null) {
        return (($state === null ? $this['state'] : $state) == SwapState::ERROR);
    }
    public function wasSent() {
        switch ($this['state']) {
            case SwapState::SENT:
            case SwapState::COMPLETE:
                return true;
        }

        return false;
    }

    public function stateMachine() {
        if (!isset($this->state_machine)) {
            $this->state_machine = app('Swapbot\Statemachines\SwapStateMachineFactory')->buildStateMachineFromSwap($this);
        }
        return $this->state_machine;
    }

}
