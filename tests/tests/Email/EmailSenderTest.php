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
        if ($this->pretend_to_send_mail) {
            Mail::pretend();
        }

        $send_email = new SendEmail('emails.notifications.welcome', $this->getSampleEmailVars(), "Request Received", "devon@tokenly.co", "Devon");
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
                $this->assertEquals  ('Request Received'                          , $msg->getSubject());
                $this->assertEquals  (['devon@tokenly.co' => 'Devon']             , $msg->getTo());
                $this->assertEquals  (['no-reply@tokenly.co' => 'Tokenly Bot']    , $msg->getFrom());
                $this->assertContains('Thanks for making a purchase with SwapBot' , $msg->getBody());
                $this->assertContains('/public/unsubscribe/12345-67890/foo123'    , $msg->getBody());
            });

        // send
        $send_email = new SendEmail('emails.notifications.welcome', $this->getSampleEmailVars(), "Request Received", "devon@tokenly.co", "Devon");
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($send_email);
    }



    ////////////////////////////////////////////////////////////////////////

    public function setUp()
    {
        parent::setUp();

        // // pretend to send mail - don't actually send it
        // if ($this->pretend_to_send_mail) {
        //     Mail::pretend();
        // }

        // use the sync queue
        Mail::setQueue(Queue::getFacadeRoot()->connection('sync'));
    }

    protected function getMailerMockContructorArgs()
    {
        return [m::mock('Illuminate\Contracts\View\Factory'), m::mock('Swift_Mailer')];
    }

    protected function getSampleEmailVars() {
        $email_vars = [
            'swap'            => [],
            'bot'             => ['confirmationsRequired' => 2, 'name' => 'Foo Bot'],
            'inQty'           => 0.2,
            'inAsset'         => 'BTC',
            'outQty'          => 2000,
            'outAsset'        => 'LTBCOIN',
            'unsubscribeLink' => 'http://foo.bar/public/unsubscribe/12345-67890/foo123',
            'robohashUrl'     => 'http://robohash.org/5a8e7572b37212f8d32817f40409a29fb9849c0e2336c6df19f4bfde9ebc720a.png?set=set3',
            'botUrl'          => 'http://foo.bar',
            'botLink'         => '<a href="http://foo.bar">http://foo.bar</a>',
            'customer'        => ['uuid' => '12345-67890', 'unsubscribe_token' => 'foo123'],
        ];
        return $email_vars;
    }

}
