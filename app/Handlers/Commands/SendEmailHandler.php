<?php

namespace Swapbot\Handlers\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Swapbot\Commands\SendEmail;

class SendEmailHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the command.
     *
     * @param  SendEmail  $command
     * @return void
     */
    public function handle(SendEmail $command)
    {

        $email_template_name = $command->email_template_name;

        try {
            $template_paramater = [$email_template_name.'-html', $email_template_name.'-txt'];

            // make sure the -html and -txt views exist
            $view_finder = app('view.finder');
            $view_finder->find($template_paramater[0]);
            $view_finder->find($template_paramater[1]);

        } catch (InvalidArgumentException $e) {
            // fallback to single view without adding any -html or -txt suffix
            $template_paramater = $email_template_name;
        }
        
        $vars = array_merge([
            'siteHost' => Config::get('swapbot.site_host'),
        ], $command->email_template_vars);

        Mail::queueOn('email', $template_paramater, $vars, function($message) use ($command)
        {
            $message
                ->to($command->recipient_email, $command->recipient_name)
                ->subject($command->subject);
        });

    }

}
