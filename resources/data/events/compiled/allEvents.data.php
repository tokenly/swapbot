<?php

// compiled on 2015-04-30 16:09:11

return array (
  'swap.new' => 
  array (
    'name' => 'swap.new',
    'label' => 'New Swap',
    'level' => 'INFO',
    'msg' => 'A new swap was created for incoming transaction {{ $tx_id }}.',
    'swapEventStream' => false,
  ),
  'tx.previous' => 
  array (
    'name' => 'tx.previous',
    'label' => 'Previous Transaction',
    'level' => 'DEBUG',
    'msg' => 'Transaction {{ $tx_id }} was confirmed with {{ $confirmations }} confirmations.',
  ),
);

