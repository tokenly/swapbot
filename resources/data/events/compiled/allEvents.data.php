<?php

// compiled on 2015-05-03 02:13:23

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

