<?php

// compiled on 2015-05-05 12:34:10

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
    'msg' => 'Confirmed <?php echo e($quantityIn); ?> <?php echo e($assetIn); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
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
    'msg' => 'Confirmed <?php echo e($quantityIn); ?> <?php echo e($assetIn); ?> from <?php echo e($destination); ?> with <?php echo e($confirmations); ?> <?php echo e(str_plural(\'confirmation\', $confirmations)); ?>.',
    'msgVars' => 
    array (
      0 => 'quantityIn',
      1 => 'assetIn',
      2 => 'destination',
      3 => 'confirmations',
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
);

