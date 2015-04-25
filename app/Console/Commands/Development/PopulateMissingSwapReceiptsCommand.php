<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PopulateMissingSwapReceiptsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:populate-missing-swap-receipts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds missing swap receipts';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setHelp(<<<EOF
Finds and updates missing swap receipts.
EOF
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['test', null, InputOption::VALUE_NONE, 'Test Mode'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $is_test = !!$this->input->getOption('test');
        $swap_repo = app('Swapbot\Repositories\SwapRepository');
        foreach ($swap_repo->findAll() as $swap) {
            $this->comment("Processing swap {$swap['uuid']}");

            $processed = $this->processSwap($swap, $swap_repo, $is_test);
            if (!$processed) {
                $this->comment("Swap {$swap['uuid']} not processed");
            }
        }
        $this->info('done');
    }

    protected function processSwap($swap, $swap_repo, $is_test) {
        $receipt = $this->buildReceipt($swap);
        if (!$receipt) { return null; }
        
        $swap_update_vars = ['receipt' => $receipt];
        if ($is_test) {
            $this->line("[TEST] New Receipt: ".json_encode($receipt, 192));
        } else {
            $swap_repo->update($swap, $swap_update_vars);
        }
        return true;
    }

    protected function buildReceipt($swap) {
        $existing_receipt = $swap['receipt'];

        if (!$existing_receipt) { return null; }

        $swap_config         = $swap->getSwapConfig();
        $transaction         = $swap->transaction;
        $xchain_notification = $transaction['xchain_notification'];

        // initialize a DTO (data transfer object) to hold all the variables for this swap
        $swap_process = [
            'swap'                => $swap,
            'swap_config'         => $swap_config,
            'swap_id'             => $swap_config->buildName(),

            'transaction'         => $transaction,

            'in_asset'            => $xchain_notification['asset'],
            'in_quantity'         => $xchain_notification['quantity'],
            'destination'         => $xchain_notification['sources'][0],
            'confirmations'       => $transaction['confirmations'],

            'quantity'            => null,
            'asset'               => null,
        ];

        // calculate the receipient's quantity and asset
        list($swap_process['quantity'], $swap_process['asset']) = $swap_process['swap_config']->getStrategy()->buildSwapOutputQuantityAndAsset($swap_process['swap_config'], $swap_process['in_quantity']);

        // build the receipt
        $receipt = [
            'type'          => $existing_receipt['type'],

            'quantityIn'    => $swap_process['in_quantity'],
            'assetIn'       => $swap_process['in_asset'],
            'txidIn'        => $swap_process['transaction']['txid'],

            'quantityOut'   => $swap_process['quantity'],
            'assetOut'      => $swap_process['asset'],

            'confirmations' => $swap_process['confirmations'],
            'destination'   => $swap_process['destination'],
        ];
        return $receipt;
    }


}
