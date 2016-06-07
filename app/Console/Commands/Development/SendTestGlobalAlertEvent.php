<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\SettingWasChanged;
use Swapbot\Models\Setting;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SendTestGlobalAlertEvent extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:send-test-global-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a global alert notification.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['content', InputArgument::OPTIONAL, 'The alert message content'],
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
            ['inactive', 'i', InputOption::VALUE_NONE, 'Make alert inactive'],
        ];
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            $content = $this->argument('content');
            if ($content === null) { $content = ''; }
            $active  = !$this->option('inactive');

            $setting = new Setting([
                'name' => 'globalAlert',
                'value' => [
                    'status'  => $active,
                    'content' => $content,
                ],
            ]);

            Event::fire(new SettingWasChanged($setting, 'create'));

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->comment('Done');
    }

}
