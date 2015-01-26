<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestConfigCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Config';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setHelp(<<<EOF
Test Config
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

        // event log test
        EventLog::log('test.config', []);

        // test XChain Provider
        $this->comment('checking xchain client');
        $this->laravel->make('Tokenly\XChainClient\Client');
        $config = $this->laravel['config']['xchain-client::xchain'];
        echo "\$config:\n".json_encode($config, 192)."\n";

    }


}
