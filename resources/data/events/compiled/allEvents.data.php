<?php

// compiled on 2015-04-30 17:07:00

return array (
  'swap.new' => 
  array (
    'name' => 'swap.new',
    'label' => 'New Swap',
    'level' => 'INFO',
    'msg' => 'A new swap was created for incoming transaction {{ $txidIn }}.',
    'swapEventStream' => false,
  ),
  'swap.transaction.update' => 
  array (
    'name' => 'swap.transaction.update',
    'label' => 'Swap Transaction',
    'level' => 'INFO',
    'msg' => 'Transaction {{ $txidIn }} was seen.',
    'swapEventStream' => true,
  ),
  'tx.previous' => 
  array (
    'name' => 'tx.previous',
    'label' => 'Previous Transaction',
    'level' => 'DEBUG',
    'msg' => 'Transaction {{ $tx_id }} was confirmed with {{ $confirmations }} confirmations.',
  ),
);

