<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Swapbot\Events\SettingWasChanged;
use Swapbot\Models\Setting;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateDynamicTests extends Command {


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:update-dynamic-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the dynamic tests.';

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
            // ['content', InputArgument::OPTIONAL, 'The content'],
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
            ['dry-run', 'd', InputOption::VALUE_NONE, 'Dry run only'],
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
            $dry_run  = !!($this->option('dry-run'));

            // do all state tests in directory
            $scenario_number_count = count(glob(base_path().'/tests/fixtures/scenarios/*.yml'));
            $dynamic_test_code = '';
            for ($i=1; $i <= $scenario_number_count; $i++) { 
                $dynamic_test_code .= <<<EOT
    public function testScenario_{$i}() { \$this->runScenario({$i}); } 

EOT;
            }

            $filepath = base_path().'/tests/tests/Scenario/ScenariosTest.php';
            $source = file_get_contents($filepath);
            $begin = '-- BEGIN DYNAMICALLY GENERATED TESTS --';
            $end = '    // -- -END- DYNAMICALLY GENERATED TESTS --';
            $source = 
                substr($source, 0, strpos($source, $begin) + strlen($begin) + 1)
                .$dynamic_test_code
                .substr($source, strpos($source, $end));

            if ($dry_run) {
                $this->info($source);
            } else {
                copy($filepath, sys_get_temp_dir().'/ScenariosTest-'.date("Ymd_His").'.php');
                file_put_contents($filepath, $source);
            }
        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->comment('Done');
    }

}
