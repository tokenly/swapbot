<?php

namespace Swapbot\Models\Data;

use ArrayObject;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Swap;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

class RefundConfig extends ArrayObject implements APISerializeable {

    protected $strategy_obj = null;

    const REASON_OUT_OF_STOCK  = 'outOfStock';
    const REASON_BELOW_MINIMUM = 'belowMinimum';
    const REASON_MANUAL_REFUND = 'manualRefund';
    const REASON_SHUTTING_DOWN = 'shuttingDown';
    const REASON_UNKNOWN       = 'unknown';
    


    function __construct($data=[]) {
        parent::__construct($data);
    }

    public static function refundReasonCodeToRefundReasonDescription($refund_reason_code) {
        switch ($refund_reason_code) {
            case self::REASON_OUT_OF_STOCK:
                $refund_reason = 'Your swap was out of stock';
                break;
            
            case self::REASON_BELOW_MINIMUM:
                $refund_reason = 'Your deposit was less than the required minimum';
                break;
            
            case self::REASON_MANUAL_REFUND:
                $refund_reason = 'Your deposit was refunded by an administrator';
                break;
            
            case self::REASON_SHUTTING_DOWN:
                $refund_reason = 'This bot is shutting down';
                break;
            
            default:
                $refund_reason = 'Your swap could not be processed';
                break;
        }

        return $refund_reason;
    }

    public static function createFromSerialized($data) {
        $swap = new RefundConfig();
        $swap->unSerialize($data);
        return $swap;
    }

    public function unSerialize($data) {
        $this['refundAfterBlocks']  = isset($data['refundAfterBlocks'])  ? $data['refundAfterBlocks']  : 72;

        return $this;
    }

    public function serialize() {
        return [
            'refundAfterBlocks'  => $this['refundAfterBlocks'],
        ];
    }

    public function serializeForAPI() { return $this->serialize(); }


    public function swapShouldBeAutomaticallyRefunded(Swap $swap, $current_block_height) {
        // get the confirmed block height of the incoming transaction
        $xchain_notification = $swap->transaction['xchain_notification'];
        $confirmed_block_height = (isset($xchain_notification['bitcoinTx']) AND isset($xchain_notification['bitcoinTx']['blockheight'])) ? $xchain_notification['bitcoinTx']['blockheight'] : null;
        Log::debug("\$confirmed_block_height=$confirmed_block_height \$current_block_height=$current_block_height");

        // if the incomeing transaction hasn't been confirmed yet, the swap should not be refunded
        if (!$confirmed_block_height) { return false; }

        // compare elpased blocks with the setting
        $blocks_elapsed = $current_block_height - $confirmed_block_height + 1;
        if ($blocks_elapsed >= $this['refundAfterBlocks']) {
            return true;
        }

        return false;
    }

}
