<?php

// compiled on 2015-06-26 12:55:32

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
    'swapEventStream' => false,
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
    'msg' => 'Received <?php echo e($quantityIn); ?> <?php echo e($assetIn); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
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
    'msg' => 'Received <?php echo e($quantityIn); ?> <?php echo e($assetIn); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
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
    'msg' => 'Received <?php echo e($quantityIn); ?> <?php echo e($assetIn); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
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
    'msg' => 'Sent <?php echo e($quantityOut); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
    ),
    'swapEventStream' => true,
  ),
  'send.unconfirmed' => 
  array (
    'name' => 'send.unconfirmed',
    'label' => 'Unconfirmed Swap Send',
    'level' => 'DEBUG',
    'msg' => 'Unconfirmed send of <?php echo e($quantityOut); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
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
    'msg' => 'Sent <?php echo e($quantityOut); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?> with <?php echo e($confirmationsOut); ?> <?php echo e(str_plural(\'confirmation\', $confirmationsOut)); ?>.',
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
    'msg' => 'Refunding <?php echo e($quantityOut); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
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
    'msg' => 'Refunded <?php echo e($quantityOut); ?> <?php echo e($assetOut); ?> to <?php echo e($destination); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityOut',
      1 => 'assetOut',
      2 => 'destination',
    ),
    'swapEventStream' => true,
  ),
  'tx.previous' => 
  array (
    'name' => 'tx.previous',
    'label' => 'Previous Transaction',
    'level' => 'DEBUG',
    'msgVars' => 
    array (
      0 => 'txid',
      1 => 'confirmations',
    ),
    'msg' => 'Transaction <?php echo e($txid); ?> was confirmed with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'botEventStream' => true,
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
    'msg' => 'Unconfirmed swapbot fuel of <?php echo e($inQty); ?> <?php echo e($inAsset); ?> received from <?php echo e($source); ?> with transaction ID <?php echo e($txid); ?>.',
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
    'msg' => 'Swapbot fuel of <?php echo e($inQty); ?> <?php echo e($inAsset); ?> received from <?php echo e($source); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
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
    'msg' => 'Received an unconfirmed payment of <?php echo e($inQty); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?>',
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
    'msg' => 'Received a confirmed payment of <?php echo e($inQty); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?>',
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
    'msg' => 'Received a payment of <?php echo e($inQty); ?> <?php echo e($inAsset); ?> from <?php echo e($source); ?> with transaction ID <?php echo e($txid); ?>. This was not a valid payment.',
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
    'msg' => 'Moving initial swapbot fuel.  Sent <?php echo e($outQty); ?> <?php echo e($outAsset); ?> to <?php echo e($destination); ?> with transaction ID <?php echo e($txid); ?>',
    'msgVars' => 
    array (
      0 => 'outQty',
      1 => 'outAsset',
      2 => 'destination',
      3 => 'txid',
    ),
  ),
);

