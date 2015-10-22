<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\File;
use Swapbot\Commands\ReceiveWebhook;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestReceiveFromXChainTemplateCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:xchain-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receives a test xchain notification from a template';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

        $files = [];
        foreach (File::files($this->templateDirectory()) as $raw_file) {
            $files[] = substr(basename($raw_file), 0, -10);
        }

        return [
            ['bot-id', InputArgument::REQUIRED, 'Bot ID'],
            ['template-type', InputArgument::REQUIRED, 'Template filename ('.implode(', ', $files).')'],
        ];
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {

        return [
            ['sender',             's',  InputOption::VALUE_OPTIONAL, 'Sender Address', '1SENDER000000111111111111111111111'],
            ['txid',               null, InputOption::VALUE_OPTIONAL, 'Transaction ID', 1],
            ['txidout',            null, InputOption::VALUE_OPTIONAL, 'Transaction ID out for Sends', null],
            ['replacing-txid',     null, InputOption::VALUE_OPTIONAL, 'Replacing Transaction ID out for Invalidations', null],
            ['trigger-send-error', null, InputOption::VALUE_NONE,     'Trigger a send error'],
            ['notification-id',    'i',  InputOption::VALUE_OPTIONAL, 'Notification ID', null],
            ['asset',              'a',  InputOption::VALUE_OPTIONAL, 'Asset', 'BTC'],
            ['quantity',           'u',  InputOption::VALUE_OPTIONAL, 'Quantity', '0.005'],
            ['confirmations',      'c',  InputOption::VALUE_OPTIONAL, 'Confirmations', '0'],
            ['persist-balances',   'p',  InputOption::VALUE_OPTIONAL, 'Persist test balances to disk', true],
            ['mock-balances-file', 'm',  InputOption::VALUE_OPTIONAL, 'Load mock balances file', null],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $bot_id = $this->input->getArgument('bot-id');
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByUuid($bot_id);
        if (!$bot) { $bot = $bot_repository->findByID($bot_id); }
        if (!$bot) { throw new Exception("Unable to find bot", 1); }

        $notification = $this->resolveNotification($bot);

        // mock xchain client so we don't try to make real calls
        $mock_builder = app('Tokenly\XChainClient\Mock\MockBuilder');
        if ($txid_out = $this->input->getOption('txidout')) {
            if (strlen($txid_out) < 51) { $txid_out = 'deadbeef00000000000000000000000000000000000000000000000000'.sprintf('%06x',$txid_out); }
            $mock_builder->setOutputTransactionID($txid_out);
        }
        if ($this->input->getOption('trigger-send-error')) {
            $this->comment("Using test error");
            $mock_builder->beginThrowingExceptionsAfterCount();
        }
        $mock = $mock_builder->installXChainMockClient();

        // load mock balances
        $mock_balances_file = $this->input->getOption('mock-balances-file');
        if ($mock_balances_file) {
            $data = json_decode(file_get_contents($mock_balances_file), true);
            if ($data) {
                $mock_builder->clearBalances();
                if (isset($data['balances'])) {
                    $this->comment("importing balances: ".json_encode($data['balances']['default']));
                    $mock_builder->importBalances($data);
                } else {
                    // new balances
                    $this->comment("setting new balances");
                    $mock_builder->setBalances($data, 'default');
                }
            }
        }

        // fire the notification webhook
        $this->dispatch(new ReceiveWebhook($notification));


        // persist the balances
        if ($mock_balances_file AND $this->input->getOption('persist-balances')) {
            $balances_data = $mock_builder->exportBalances();
            $this->comment('persisting balances to disk: '.json_encode($balances_data['balances']['default']));
            file_put_contents($mock_balances_file, json_encode($balances_data, 192));
        }


        $this->info("done");
    }

    public function templateDirectory() {
        return realpath(base_path('resources/views/transactions/template'));
    }

    protected function resolveNotification($bot) {
        $template_name = $this->input->getArgument('template-type');;

        $notification_id = $this->input->getOption('notification-id');
        if (!$notification_id) { $notification_id = '11111111-1111-1111-1111-'.sprintf('%06x',rand(1,16777215)).sprintf('%06x',rand(1,16777215)); }
        // echo "\$notification_id: ".json_encode($notification_id, 192)."\n";

        $txid = $this->input->getOption('txid');
        if (strlen($txid) < 51) { $txid = 'deadbeef00000000000000000000000000000000000000000000000000'.sprintf('%06x',$txid); }

        if (strlen($replacing_txid = $this->input->getOption('replacing-txid'))) {
            if (strlen($replacing_txid) < 51) { $replacing_txid = 'deadbeef00000000000000000000000000000000000000000000000000'.sprintf('%06x',$replacing_txid); }
        }

        $sender = $this->input->getOption('sender');
        if (strlen($sender) < 30) { $sender = '1SENDER000000000000000000000'.sprintf('%06x',$sender); }

        $vars = [
            'asset'          => $this->input->getOption('asset'),
            'quantity'       => $this->input->getOption('quantity'),
            'sender'         => $sender,
            'confirmations'  => $this->input->getOption('confirmations'),
            'txid'           => $txid,
            'replacingTxid'  => $replacing_txid,
            'notificationId' => $notification_id,
            'timestamp'      => time(),
            'bot'            => $bot,
        ];
        $this->comment("Sending {$template_name} notification of {$vars['quantity']} {$vars['asset']} from $sender to bot {$bot['name']}");
        $rendered_view = view('transactions.template.'.$template_name, $vars)->render();
        $resolved_notification = json_decode($rendered_view, true);
        return $resolved_notification;
    }
}
