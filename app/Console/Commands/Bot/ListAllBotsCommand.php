<?php

namespace Swapbot\Console\Commands\Bot;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBotBalances;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ListAllBotsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'swapbot:list-bots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List All Bots';


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
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
            ['user', 'u', InputOption::VALUE_NONE, 'Include User Information'],
            ['active', 'a', InputOption::VALUE_NONE, 'Active Only'],
            ['id', 'i', InputOption::VALUE_OPTIONAL, 'Filter by ID'],
            ['full', 'f', InputOption::VALUE_NONE, 'Show Full Bot Details'],
            ['balances', 'b', InputOption::VALUE_NONE, 'Include Bot Balances'],
            ['csv', 'c', InputOption::VALUE_OPTIONAL, 'Export to CSV file'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        $bot_repository = $this->laravel->make('Swapbot\Repositories\BotRepository');
        $id = $this->input->getOption('id');
        if ($id) {
            $bot = $bot_repository->findByID($id);
            if ($bot) {
                $bots[] = $bot;
            } else {
                $bots = [];
            }
        } else {
            $bots = $bot_repository->findAll();
        }

        $show_full = !!$this->input->getOption('full');
        $active_only = !!$this->input->getOption('active');
        $with_user = !!$this->input->getOption('user');
        $with_balances = !!$this->input->getOption('balances');
        $csv_filepath = $this->input->getOption('csv');

        $csv_fd = null;
        if ($csv_filepath) {
            $csv_fd = fopen($csv_filepath, 'w');
            if (!$csv_fd) {
                throw new Exception("Failed to open CSV file at $csv_filepath", 1);
            }
        }

        $csv_rows = [];
        foreach($bots as $bot) {
            $output_data = [];
            try {
                if ($active_only) {
                    if (!$bot->isActive() OR $bot->isShuttingDown()) {
                        continue;
                    }
                }
                if ($show_full) {
                    $output_data = json_decode(json_encode($bot, 192), true);
                } else {
                    $fields = ['id', 'uuid', 'name', 'username', 'address', 'state'];
                    $short_bot = [];
                    foreach($fields as $field) {
                        $short_bot[$field] = $bot[$field];
                    }
                    $output_data = json_decode(json_encode($short_bot, 192), true);
                }

                $output_data['publicUrl'] = $bot->getPublicBotURL();

                if ($with_user) {
                    $user = $bot->user;
                    $output_data['user'] = [
                        'name'  => $user['name'],
                        'email' => $user['email'],
                    ];
                }

                if ($with_balances) {
                    // refresh balances
                    Log::debug("Refreshing balance for bot ".$bot['name']);
                    try {
                        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch(new UpdateBotBalances($bot));
                        $balances = $this->formatBalances($bot['balances']);
                    } catch (Exception $e) {
                        Log::error("Error with bot balances ".$bot['name'].": ".$e->getMessage());
                        $balances = '';
                    }
                    $output_data['balances'] = $balances;
                }

                if ($csv_fd) {
                    $csv_rows[] = $output_data;
                }

                if (!$csv_fd) {
                    $output = json_encode($output_data, 192);
                    $this->line($output);
                }
            } catch (Exception $e) {
                $this->error("Error with bot ".$bot['name'].": ".$e->getMessage());
                $csv_rows[] = $output_data;
            }
        }

        if ($csv_fd) {
            $csv_headers_map = [];
            foreach($csv_rows as $csv_row) {
                $csv_headers_map = array_merge($csv_row, $csv_headers_map);
            }
            $headers = array_keys($csv_headers_map);
            $prototype_row = array_fill_keys($headers, '');

            // headers
            fputcsv($csv_fd, $headers);

            // csv rows
            foreach($csv_rows as $csv_row) {
                $row = array_merge($prototype_row, $csv_row);

                $formatted_row = [];
                foreach($row as $row_key => $row_val) {
                    if (is_array($row_val)) {
                        $row_val = json_encode($row_val);
                    }

                    $formatted_row[] = $row_val;
                }

                fputcsv($csv_fd, $formatted_row);
            }

            fclose($csv_fd);
            $this->info("Wrote CSV file at $csv_filepath");
        }

        $this->comment('done');
    }

    protected function formatBalances($balances) {
        return json_encode($balances, 192);
    }

}
