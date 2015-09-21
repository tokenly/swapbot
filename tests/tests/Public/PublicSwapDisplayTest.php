<?php

use Swapbot\Commands\ReceiveWebhook;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\LaravelEventLog\TestingEventLog;
use \PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Http\Request;

class PublicSwapDisplayTest extends TestCase {

    protected $use_database = true;



    public function testPublicSwapDisplay()
    {
        $swap = app('SwapHelper')->newSampleSwap();
        $username = $swap->bot['username'];
        $swap_uuid = $swap['uuid'];

        $url = "/swap/{$username}/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        PHPUnit::assertContains("Sample Bot One Swap Details", $response->getContent(), "Content not found in GET ".$url);


        // redirect old UUID
        $url = "/public/{$username}/swap/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(301, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        PHPUnit::assertContains("/swap/{$username}/{$swap_uuid}", $response->headers->get('location'), "location not matched");
    }


    public function testPublicSwapRequirements()
    {
        $swap = app('SwapHelper')->newSampleSwap();
        $username = $swap->bot['username'];
        $swap_uuid = $swap['uuid'];

        $url = "/swap/badusername/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $url = "/swap/{$username}/unknownuuid";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $user_2 = app('UserHelper')->getSampleUser('sample2@tokenly.co');
        $username_2 = $user_2['username'];
        $url = "/swap/{$username_2}/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

    }



}
