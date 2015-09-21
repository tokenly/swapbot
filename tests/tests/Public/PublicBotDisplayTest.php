<?php

use Swapbot\Commands\ReceiveWebhook;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\LaravelEventLog\TestingEventLog;
use \PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Http\Request;

class PublicBotDisplayTest extends TestCase {

    protected $use_database = true;



    public function testPublicBotDisplay()
    {
        $bot = app('BotHelper')->newSampleBot();
        $username = $bot['username'];
        $bot_uuid = $bot['uuid'];
        $bot_slug = $bot['url_slug'];

        // lookup by slug
        $url = "/bot/{$username}/{$bot_slug}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        PHPUnit::assertContains("Sample Bot One", $response->getContent(), "Content not found in GET ".$url);
        PHPUnit::assertContains($bot_uuid, $response->getContent(), "Content not found in GET ".$url);

        // redirect UUID
        $url = "/bot/{$username}/{$bot_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(301, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        PHPUnit::assertContains("/bot/t_SAMPLE@TOKENLY/sample-bot-one", $response->headers->get('location'), "location not matched");

        // redirect old UUID
        $url = "/public/{$username}/{$bot_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(301, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        PHPUnit::assertContains("/bot/t_SAMPLE@TOKENLY/sample-bot-one", $response->headers->get('location'), "location not matched");

    }


    public function testPublicBotRequirements()
    {
        $bot = app('BotHelper')->newSampleBot();
        $username = $bot['username'];
        $bot_uuid = $bot['uuid'];

        $url = "/bot/badusername/{$bot_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $url = "/bot/{$username}/unknownuuid";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $user_2 = app('UserHelper')->getSampleUser('sample2@tokenly.co');
        $username_2 = $user_2['username'];
        $url = "/bot/{$username_2}/{$bot_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

    }



}
