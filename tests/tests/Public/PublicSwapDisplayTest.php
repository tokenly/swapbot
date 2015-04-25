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

        $url = "/public/{$username}/swap/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
    }


    public function testPublicSwapRequirements()
    {
        $swap = app('SwapHelper')->newSampleSwap();
        $username = $swap->bot['username'];
        $swap_uuid = $swap['uuid'];

        $url = "/public/badusername/swap/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $url = "/public/{$username}/swap/unknownuuid";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        $user_2 = app('UserHelper')->getSampleUser('sample2@tokenly.co');
        $username_2 = $user_2['username'];
        $url = "/public/{$username_2}/swap/{$swap_uuid}";
        $request = Request::create($url, 'GET', []);
        $response = app('Illuminate\Contracts\Http\Kernel')->handle($request);
        PHPUnit::assertEquals(404, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

    }



}
