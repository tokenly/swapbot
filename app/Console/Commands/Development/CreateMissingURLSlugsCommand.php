<?php

namespace Swapbot\Console\Commands\Development;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Util\Slug\Slugifier;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateMissingURLSlugsCommand extends Command {

    use DispatchesCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbotdev:create-missing-url-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a URL Slug for every bot that doesn\'t have one yet';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        try {
            $bot_repository = app('Swapbot\Repositories\BotRepository');

            // load all existing bot slugs in memory
            $used_slugs = [];
            foreach ($bot_repository->findAll() as $bot) {
                $used_slugs[$bot['url_slug']] = true;
            }

            // update all slugs (once)
            foreach ($bot_repository->findAll() as $bot) {
                $attempt = 0;
                $slug_base = Slugifier::slugify($bot['name']);
                while (true) {
                    $attempt++;
                    $new_slug = $slug_base;
                    if ($attempt > 1) {
                        $new_slug .= "-".($attempt);
                    }

                    if (isset($used_slugs[$new_slug])) { continue; }

                    // update the slug
                    $bot_repository->update($bot, ['url_slug' => $new_slug]);
                    $used_slugs[$new_slug] = true;
                    break;
                }
            }

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            throw $e;
        }

        $this->info('done');
    }

}
