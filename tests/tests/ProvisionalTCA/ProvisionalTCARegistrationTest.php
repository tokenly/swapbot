<?php

use Swapbot\Models\Data\BotStateEvent;
use \PHPUnit_Framework_Assert as PHPUnit;

class ProvisionalTCARegistrationTest extends TestCase {

    protected $use_database = true;

    public function testTokenpassMock() {
        // setup xchain mocks
        $xchain_mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();
        $tokenpass_calls = app('TokenpassHelper')->mockTokenpassAPI();

        app('BotHelper')->newSampleBot(null, []);

        // mark the bot as active
        $tokenpass = app('Tokenly\TokenpassClient\TokenpassAPI');
        $tokenpass->registerProvisionalSource('1fooaddress', 'xxmyproofxx');
        $tokenpass->registerProvisionalSource('2fooaddress', 'xxmyproof2xx');

        // check and see if it registered with tokenpass
        PHPUnit::assertNotEmpty($tokenpass_calls->getCalls(), "No Tokenpass calls were made");
        PHPUnit::assertEquals('xxmyproofxx', $tokenpass_calls->getCallArgument(0, 1));
        PHPUnit::assertEquals('2fooaddress', $tokenpass_calls->getCallArgument(1, 0));
        PHPUnit::assertEquals('registerProvisionalSource', $tokenpass_calls->getCallMethod(0));
    }

    public function testSwapbotRegistersAsProvisionalTCASource() {
        // setup xchain mocks
        $xchain_mock = app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();
        $tokenpass_calls = app('TokenpassHelper')->mockTokenpassAPI();

        $bot = app('BotHelper')->newSampleBot(null, []);
        PHPUnit::assertFalse($bot['registered_with_tokenpass']);

        // mark the bot as active
        $bot->stateMachine()->triggerEvent(BotStateEvent::FIRST_MONTHLY_FEE_PAID);

        // check and see if it registered with tokenpass
        $calls = $tokenpass_calls->getCalls();
        // echo "\$calls: ".json_encode($calls, 192)."\n";
        PHPUnit::assertNotEmpty($calls, "No Tokenpass calls were made");
        PHPUnit::assertEquals('getProvisionalSourceProofSuffix', $tokenpass_calls->getCallMethod(0));
        PHPUnit::assertEquals('registerProvisionalSource', $tokenpass_calls->getCallMethod(1));
        PHPUnit::assertEquals($bot['address'], $tokenpass_calls->getCallArgument(1, 0));

        // check $xchain_mock calls
        PHPUnit::assertNotEmpty($xchain_mock->calls, "No XChain calls were made");
        // echo "\$xchain_mock->calls: ".json_encode($xchain_mock->calls, 192)."\n";
        PHPUnit::assertStringStartsWith('/message/sign/', $xchain_mock->calls[1]['path']);

        // check the bot was registered
        $reloaded_bot = app('Swapbot\Repositories\BotRepository')->findById($bot['id']);
        PHPUnit::assertTrue($reloaded_bot['registered_with_tokenpass']);
    }


    // ------------------------------------------------------------------------
    
}
