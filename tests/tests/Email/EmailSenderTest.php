<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Swapbot\Commands\SendEmail;
use Mockery as m;
use \PHPUnit_Framework_Assert as PHPUnit;

class EmailSenderTest extends TestCase {

    protected $use_database         = false;
    protected $pretend_to_send_mail = true;

    public function testSendEmail()
    {
        $send_email = new SendEmail('emails.notifications.welcome', [], "Request Received", "devon@tokenly.co", "Devon");
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($send_email);
    }

    public function testEmailParameters()
    {
        // mock
        $mock = m::mock('Swift_Mailer');
        app('mailer')->setSwiftMailer($mock);

        // expect
        $mock
            ->shouldReceive('send')->once()
            ->andReturnUsing(function(\Swift_Message $msg) {
                $this->assertEquals  ('Request Received'                      , $msg->getSubject());
                $this->assertEquals  (['devon@tokenly.co' => 'Devon']         , $msg->getTo());
                $this->assertEquals  (['no-reply@tokenly.co' => 'Tokenly Bot'], $msg->getFrom());
                $this->assertContains('Your email was received'               , $msg->getBody());
            });

        // send
        $send_email = new SendEmail('emails.notifications.welcome', [], "Request Received", "devon@tokenly.co", "Devon");
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($send_email);
    }



    ////////////////////////////////////////////////////////////////////////

    public function setUp()
    {
        parent::setUp();

        // pretend to send mail - don't actually send it
        if ($this->pretend_to_send_mail) {
            Mail::pretend();
        }

        // use the sync queue
        Mail::setQueue(Queue::getFacadeRoot()->connection('sync'));
    }

    protected function getMailerMockContructorArgs()
    {
        return [m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer')];
    }

}
