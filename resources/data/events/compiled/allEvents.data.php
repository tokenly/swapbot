<?php

// compiled on 2015-08-01 13:40:20

return array (
  'swap.new' => 
  array (
    'name' => 'swap.new',
    'label' => 'New Swap',
    'level' => 'INFO',
    'msg' => 'A new swap was created for incoming transaction <?php echo e($txidIn); ?>.',
    'msgVars' => 
    array (
      0 => 'txidIn',
    ),
    'swapEventStream' => true,
  ),
  'swap.stateChange' => 
  array (
    'name' => 'swap.stateChange',
    'label' => 'Swap State Change',
    'level' => 'DEBUG',
    'msg' => 'Entered state <?php echo e($state); ?>',
    'msgVars' => 
    array (
      0 => 'state',
    ),
    'eventVars' => 
    array (
      0 => 'state',
      1 => 'isComplete',
      2 => 'isError',
    ),
    'swapEventStream' => true,
  ),
  'swap.transaction.update' => 
  array (
    'name' => 'swap.transaction.update',
    'label' => 'Swap Transaction',
    'level' => 'INFO',
    'msg' => 'Received <?php echo e($currency($quantityIn)); ?> <?php echo e($assetIn); ?><?php echo e($swap ? \' \'.$fmt->fiatSuffix($swap->getSwapConfigStrategy(), $quantityIn, $assetIn) : \'\'); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityIn',
      1 => 'assetIn',
      2 => 'destination',
      3 => 'confirmations',
    ),
    'swapEventStream' => true,
  ),
  'swap.confirming' => 
  array (
    'name' => 'swap.confirming',
    'label' => 'Confirming Swap',
    'level' => 'INFO',
    'msg' => 'Received <?php echo e($currency($quantityIn)); ?> <?php echo e($assetIn); ?><?php echo e($swap ? \' \'.$fmt->fiatSuffix($swap->getSwapConfigStrategy(), $quantityIn, $assetIn) : \'\'); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityIn',
      1 => 'assetIn',
      2 => 'destination',
      3 => 'confirmations',
    ),
    'swapEventStream' => true,
  ),
  'swap.confirmed' => 
  array (
    'name' => 'swap.confirmed',
    'label' => 'Confirmed Swap',
    'level' => 'INFO',
    'msg' => 'Received <?php echo e($currency($quantityIn)); ?> <?php echo e($assetIn); ?><?php echo e($swap ? \' \'.$fmt->fiatSuffix($swap->getSwapConfigStrategy(), $quantityIn, $assetIn) : \'\'); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityIn',
      1 => 'assetIn',
      2 => 'destination',
      3 => 'confirmations',
    ),
    'swapEventStream' => true,
  ),
  'swap.sent' => 
  array (
    'name' => 'swap.sent',
    'label' => 'Swap Sent',
    'level' => 'INFO',
    'msg' => 'Sent <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?><?php echo e((isset($changeOut) AND $changeOut > 0) ? " and {$currency($changeOut)} {$changeOutAsset} in change" : ""); ?> to <?php echo e($destination); ?>.
',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
      3 => 'changeOut',
      4 => 'changeOutAsset',
    ),
    'swapEventStream' => true,
  ),
  'send.unconfirmed' => 
  array (
    'name' => 'send.unconfirmed',
    'label' => 'Unconfirmed Swap Send',
    'level' => 'DEBUG',
    'msg' => 'Unconfirmed send of <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
    ),
    'swapEventStream' => true,
  ),
  'send.confirmed' => 
  array (
    'name' => 'send.confirmed',
    'label' => 'Swap Send Confirmed',
    'level' => 'INFO',
    'msg' => 'Sent <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?><?php echo e((isset($changeOut) AND $changeOut > 0) ? " and {$currency($changeOut)} {$changeOutAsset} in change" : ""); ?> to <?php echo e($destination); ?> with <?php echo e($confirmationsOut); ?> <?php echo e(str_plural(\'confirmation\', $confirmationsOut)); ?>.
',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
      3 => 'confirmationsOut',
    ),
    'swapEventStream' => true,
  ),
  'swap.failed' => 
  array (
    'name' => 'swap.failed',
    'label' => 'Swap Failed',
    'level' => 'WARNING',
    'msg' => 'This swap send attempt failed.',
    'msgVars' => 
    array (
    ),
    'swapEventStream' => true,
  ),
  'swap.notReady' => 
  array (
    'name' => 'swap.notReady',
    'label' => 'Swap Not Ready',
    'level' => 'WARNING',
    'msg' => 'This swap could not be processed because it was not ready.',
    'msgVars' => 
    array (
    ),
    'swapEventStream' => true,
  ),
  'swap.refunding' => 
  array (
    'name' => 'swap.refunding',
    'label' => 'Refunding Swap',
    'level' => 'DEBUG',
    'msg' => 'Refunding <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
    ),
    'swapEventStream' => true,
  ),
  'swap.refunded' => 
  array (
    'name' => 'swap.refunded',
    'label' => 'Swap Refunded',
    'level' => 'INFO',
    'msg' => 'Refunded <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
    ),
    'swapEventStream' => true,
  ),
  'income.forwarded' => 
  array (
    'name' => 'income.forwarded',
    'label' => 'Income Forwarded',
    'level' => 'INFO',
    'msg' => 'Sent an income forwarding payment of <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?> with transaction ID <?php echo e($txid); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
      3 => 'txid',
    ),
    'botEventStream' => true,
  ),
  'income.forwardSent' => 
  array (
    'name' => 'income.forwardSent',
    'label' => 'Income Forwarding Sent',
    'level' => 'DEBUG',
    'msg' => 'Income of <?php echo e($currency($quantityOut)); ?> <?php echo e($assetOut); ?> was forwarded to <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
      3 => 'confirmations',
    ),
    'botEventStream' => true,
  ),
  'tx.previous' => 
  array (
    'name' => 'tx.previous',
    'label' => 'Previous Transaction',
    'level' => 'DEBUG',
    'msg' => 'Transaction <?php echo e($txid); ?> was confirmed with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'txid',
      1 => 'confirmations',
    ),
  ),
  'bot.stateChange' => 
  array (
    'name' => 'bot.stateChange',
    'label' => 'Bot State Change',
    'level' => 'DEBUG',
    'msg' => 'Bot entered state <?php echo e($state); ?>.',
    'msgVars' => 
    array (
      0 => 'state',
    ),
    'botEventStream' => true,
  ),
  'payment.unconfirmedMoveFuel' => 
  array (
    'name' => 'payment.unconfirmedMoveFuel',
    'label' => 'Unconfirmed Swapbot Fuel Received',
    'level' => 'INFO',
    'msg' => 'Unconfirmed swapbot fuel of <?php echo e($currency($inQty)); ?> <?php echo e($inAsset); ?> received from <?php echo e($source); ?> with transaction ID <?php echo e($txid); ?>.',
    'msgVars' => 
    array (
      0 => 'inQty',
      1 => 'inAsset',
      2 => 'source',
      3 => 'txid',
    ),
  ),
  'payment.moveFuelConfirmed' => 
  array (
    'name' => 'payment.moveFuelConfirmed',
    'label' => 'Swapbot Fuel Received',
    'level' => 'INFO',
    'msg' => 'Swapbot fuel of <?php echo e($currency($inQty)); ?> <?php echo e($inAsset); ?> received from <?php echo e($source); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'inQty',
      1 => 'inAsset',
      2 => 'source',
      3 => 'txid',
      4 => 'confirmations',
    ),
  ),
  'payment.leaseCreated' => 
  array (
    'name' => 'payment.leaseCreated',
    'label' => 'Lease Created',
    'level' => 'INFO',
    'msg' => 'Swapbot lease activated from <?php echo e($start_date); ?> until <?php echo e($end_date); ?>.',
    'msgVars' => 
    array (
      0 => 'start_date',
      1 => 'end_date',
    ),
  ),
  'payment.monthlyFeePurchased' => 
  array (
    'name' => 'payment.monthlyFeePurchased',
    'label' => 'Monthly Fee Purchased',
    'level' => 'INFO',
    'msg' => 'Purchased <?php echo e($months); ?> <?php echo e(str_plural(\'month\', $months)); ?> of swapbot rental for <?php echo e($cost); ?> <?php echo e($asset); ?>.',
    'msgVars' => 
    array (
      0 => 'months',
      1 => 'cost',
      2 => 'asset',
    ),
  ),
  'payment.firstMonthlyFeePaid' => 
  array (
    'name' => 'payment.firstMonthlyFeePaid',
    'label' => 'First Monthly Fee Paid',
    'level' => 'INFO',
    'msg' => 'Paid <?php echo e($qty); ?> <?php echo e($asset); ?> as a monthly fee.',
    'msgVars' => 
    array (
      0 => 'qty',
      1 => 'asset',
    ),
  ),
  'payment.monthlyFeePaid' => 
  array (
    'name' => 'payment.monthlyFeePaid',
    'label' => 'Monthly Fee Paid',
    'level' => 'INFO',
    'msg' => 'Paid <?php echo e($qty); ?> <?php echo e($asset); ?> as a monthly fee.',
    'msgVars' => 
    array (
      0 => 'qty',
      1 => 'asset',
    ),
  ),
  'payment.unconfirmed' => 
  array (
    'name' => 'payment.unconfirmed',
    'label' => 'Unconfirmed Payment Received',
    'level' => 'INFO',
    'msg' => 'Received an unconfirmed payment of <?php echo e($currency($inQty)); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?>',
    'msgVars' => 
    array (
      0 => 'inQty',
      1 => 'inAsset',
      2 => 'source',
    ),
  ),
  'payment.confirmed' => 
  array (
    'name' => 'payment.confirmed',
    'label' => 'Confirmed Payment Received',
    'level' => 'INFO',
    'msg' => 'Received a confirmed payment of <?php echo e($currency($inQty)); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?>',
    'msgVars' => 
    array (
      0 => 'inQty',
      1 => 'inAsset',
      2 => 'source',
    ),
  ),
  'payment.previous' => 
  array (
    'name' => 'payment.previous',
    'label' => 'Confirmed Payment Received',
    'level' => 'DEBUG',
    'msg' => 'Payment transaction <?php echo e($txid); ?> was confirmed with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'txid',
      1 => 'confirmations',
    ),
  ),
  'payment.unknown' => 
  array (
    'name' => 'payment.unknown',
    'label' => 'Confirmed Payment Received',
    'level' => 'WARNING',
    'msg' => 'Received a payment of <?php echo e($currency($inQty)); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?> with transaction ID <?php echo e($txid); ?>. This was not a valid payment.',
    'msgVars' => 
    array (
      0 => 'inQty',
      1 => 'inAsset',
      2 => 'source',
      3 => 'txid',
    ),
  ),
  'payment.moveFuelCreated' => 
  array (
    'name' => 'payment.moveFuelCreated',
    'label' => 'Move Fuel Transaction Created',
    'level' => 'DEBUG',
    'msg' => 'Moving initial swapbot fuel.  Sent <?php echo e($currency($outQty)); ?> <?php echo e($outAsset); ?> to <?php echo e($destination); ?> with transaction ID <?php echo e($txid); ?>',
    'msgVars' => 
    array (
      0 => 'outQty',
      1 => 'outAsset',
      2 => 'destination',
      3 => 'txid',
    ),
  ),
  'payment.forwarded' => 
  array (
    'name' => 'payment.forwarded',
    'label' => 'Payment Forwarded',
    'level' => 'INFO',
    'msg' => 'Forwarded a payment of <?php echo e($currency($outQty)); ?> <?php echo e($outAsset); ?> to <?php echo e($destination); ?> with transaction ID <?php echo e($txid); ?>',
    'msgVars' => 
    array (
      0 => 'outQty',
      1 => 'outAsset',
      2 => 'destination',
      3 => 'txid',
    ),
  ),
  'account.transferIncome' => 
  array (
    'name' => 'account.transferIncome',
    'label' => 'Transfer Income',
    'level' => 'DEBUG',
    'msg' => 'Transferred Income <?php echo e($currency($quantity)); ?> <?php echo e($asset); ?> for txid <?php echo e($from); ?> to <?php echo e($to); ?>.',
    'msgVars' => 
    array (
      0 => 'quantity',
      1 => 'asset',
      2 => 'from',
      3 => 'to',
    ),
  ),
  'account.transferInventory' => 
  array (
    'name' => 'account.transferInventory',
    'label' => 'Transfer Inventory',
    'level' => 'DEBUG',
    'msg' => 'Transferred inventory of <?php echo e($currency($quantity)); ?> <?php echo e($asset); ?> from account <?php echo e($from); ?> to <?php echo e($to); ?>.',
    'msgVars' => 
    array (
      0 => 'quantity',
      1 => 'asset',
      2 => 'from',
      3 => 'to',
    ),
  ),
  'account.transferInventoryFailed' => 
  array (
    'name' => 'account.transferInventoryFailed',
    'label' => 'Transfer Inventory Failed',
    'level' => 'WARNING',
    'msg' => 'Failed to transfer inventory of <?php echo e($currency($quantity)); ?> <?php echo e($asset); ?> from account <?php echo e($from); ?> to <?php echo e($to); ?>.',
    'msgVars' => 
    array (
      0 => 'quantity',
      1 => 'asset',
      2 => 'from',
      3 => 'to',
    ),
  ),
  'account.closeSwapAccount' => 
  array (
    'name' => 'account.closeSwapAccount',
    'label' => 'Swap Account Closed',
    'level' => 'DEBUG',
    'msg' => 'Closed swap account.',
    'msgVars' => 
    array (
    ),
  ),
  'account.closeSwapAccountFailed' => 
  array (
    'name' => 'account.closeSwapAccountFailed',
    'label' => 'Swap Account Close Failed',
    'level' => 'WARNING',
    'msg' => 'Failed to close swap account.',
    'msgVars' => 
    array (
    ),
  ),
  'bot.balancesSynced' => 
  array (
    'name' => 'bot.balancesSynced',
    'label' => 'Bot Balances Synced',
    'level' => 'DEBUG',
    'msg' => 'Bot balances were synced.',
    'msgVars' => 
    array (
      0 => 'all_balances_by_type',
    ),
  ),
  'bot.balancesSyncFailed' => 
  array (
    'name' => 'bot.balancesSyncFailed',
    'label' => 'Bot Balances Sync Failed',
    'level' => 'WARNING',
    'msg' => 'Failed to sync bot balances',
    'msgVars' => 
    array (
    ),
  ),
);

