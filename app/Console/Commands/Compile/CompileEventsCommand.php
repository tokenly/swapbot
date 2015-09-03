<?php

namespace Swapbot\Console\Commands\Compile;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Tokenly\LaravelEventLog\Facade\EventLog;

class CompileEventsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:compile-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compiles Events';


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

        // read the YAML
        $yaml_filepath = realpath(base_path('resources/data/events/source/allEvents.yml'));
        $parsed_data = Yaml::parse(file_get_contents($yaml_filepath));

        // resolve the data
        $events_by_name = [];
        foreach ($parsed_data['events'] as $event) {
            // compile msg
            if (isset($event['msg'])) {
                $event['msg'] = trim(Blade::compileString($event['msg']));
            }

            $events_by_name[$event['name']] = $event;
        }

        // save the data as a PHP array
        $compiled_php_filepath = realpath(base_path('resources/data/events/compiled')).'/allEvents.data.php';
        file_put_contents($compiled_php_filepath, '<?php'."\n\n".'// compiled on '.date("Y-m-d H:i:s")."\n\n".'return '.var_export($events_by_name, true).';'."\n\n");


        $this->info('done');
    }



}
