<?php

// compiled on 2015-05-07 02:49:15

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
    'msg' => 'Transaction <?php echo e($txid); ?> was confirmed with <?php echo e($confirmations); ?> confirmations.',
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
);

