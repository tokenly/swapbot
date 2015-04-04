<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\ReceiveWebhook;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestReceiveXChainNotificationCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:receive-xchain-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receives a test xchain notification';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('notification', InputArgument::REQUIRED, 'Notification JSON')
            ->setHelp(<<<EOF
Receives a test notification
EOF
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        // mock xchain client so we don't try to make real calls
        $mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();

        // try a file
        $notification_arg = $this->input->getArgument('notification');
        if (strstr($notification_arg, '{')) {
            // interpret as raw JSON
            $notification = json_decode($notification_arg);
        } else {
            // assume file
            if (file_exists($notification_arg)) {
                $notification = json_decode(file_get_contents($notification_arg), true);
            } else {
                $this->error("File $notification_arg not found");
                return;
            }
        }

        if (!$notification) {
            throw new Exception("Unable to decode notification", 1);
        }

        // fire the notification webhook
        $this->dispatch(new ReceiveWebhook($notification));


        $this->info("done");
    }

}
