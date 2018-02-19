<?php

namespace Swapbot\Console\Commands\User;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Event;
use Swapbot\Commands\ActivateBot;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class EnableUserCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:enable-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enables users to create new bots';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('user-id-or-email', InputArgument::REQUIRED, 'User ID or Email or all')
            ->addOption('disable', 'd', InputOption::VALUE_NONE, 'Disable user instead of enabling')
            ->setHelp(<<<EOF
Enables a user to create new bots
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
        $user_id_or_email = $this->input->getArgument('user-id-or-email');
        $disable = !!$this->input->getOption('disable');

        $user_repository = $this->laravel->make('Swapbot\Repositories\UserRepository');
        if ($user_id_or_email == 'all') {
            $users = $user_repository->findAll();
        } else {
            $user = $user_repository->findByUuid($user_id_or_email);
            if (!$user) { $user = $user_repository->findByEmail($user_id_or_email); }
            if (!$user) {
                throw new Exception("Unable to find user", 1);
            }
            $users = [$user];
        }

        foreach($users as $user) {
            $this->info(($disable ? "Disabling" : "Enabling ")." user ".$user['name']." ({$user['uuid']})");

            try {
                $privileges = $user['privileges'];
                if (!$privileges) {
                    $privileges = [];
                }
                if ($disable) {
                    unset($privileges['createNewBot']);
                } else {
                    $privileges['createNewBot'] = true;
                }

                $user_repository->update($user, ['privileges' => $privileges]);
            } catch (Exception $e) {
                // log any failure
                EventLog::logError('activate.failed', $e);
                $this->error("Failed: ".$e->getMessage());
            }
        }


        $this->info("done");
    }

}
