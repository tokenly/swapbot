<?php

namespace Swapbot\Models;

use Illuminate\Database\Eloquent\Model;
use Swapbot\Models\Data\SwapConfig;
use Swapbot\Models\Data\SwapState;

class Swap extends Model {

    protected $state_machine        = null;

    protected $casts = [
        'definition' => 'json',
        'receipt'    => 'json',
    ];

    protected static $unguarded = true;

    public function setInQtyAttribute($in_qty) { $this->attributes['in_qty'] = CurrencyUtil::valueToSatoshis($in_qty); }
    public function getInQtyAttribute() { return isset($this->attributes['in_qty']) ? CurrencyUtil::satoshisToValue($this->attributes['in_qty']) : 0; }


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
    public function isComplete() {
        return ($this['state'] == SwapState::COMPLETE);
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
