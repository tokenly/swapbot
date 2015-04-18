<?php

namespace Swapbot\Commands;

use Swapbot\Commands\Command;

class SendEmail extends Command {

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($email_template_name, $email_template_vars, $subject, $recipient_email, $recipient_name=null)
    {
        $this->email_template_name = $email_template_name;
        $this->email_template_vars = $email_template_vars;
        $this->subject = $subject;
        $this->recipient_email = $recipient_email;
        $this->recipient_name = $recipient_name;
    }

}
