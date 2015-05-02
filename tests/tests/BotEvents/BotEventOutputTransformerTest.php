<?php

use Swapbot\Swap\Logger\OutputTransformer\Facade\BotEventOutputTransformer;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotEventOutputTransformerTest extends TestCase {

    protected $use_database = true;

    public function testBotEventOutputTransformer()
    {
        $logger = app('Swapbot\Swap\Logger\BotEventLogger');
        $swap = app('SwapHelper')->newSampleSwap();

        $logger->logNewSwap($swap->bot, $swap, [
            'txidIn'        => '0000000MySampleTxID000000000000000001',
        ]);

        // load the bot event
        $bot_event = app('Swapbot\Repositories\BotEventRepository')->findAll()->first();



        // test the resolved msg
        $message = BotEventOutputTransformer::buildMessage($bot_event);
        PHPUnit::assertEquals("A new swap was created for incoming transaction 0000000MySampleTxID000000000000000001.", $message);
    }


}