<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class AvailableSwapsAPITest extends TestCase {

    protected $use_database = true;

    public function testAvailableSwapsAPI()
    {

        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot($this->swaps1(), 'bot 001'); // $3.33
        $bot_2 = $this->buildBot($this->swaps2(), 'bot 002'); // $3.33
        $bot_3 = $this->buildBot($this->swaps3(), 'bot 003'); // $5.00
        $bot_4 = $this->buildBot($this->swaps4(), 'bot 004'); // $5.00

        ////////////////////////////////////////////////////////////////////////
        // cost, name

        $found_available_swaps = $this->runSearch(['inToken' => 'TOKENLY' ]);
        PHPUnit::assertCount(4, $found_available_swaps);
        PHPUnit::assertEquals($bot_2['uuid'], $found_available_swaps[0]['bot']['id']);
        PHPUnit::assertEquals('TOKENLY', $found_available_swaps[0]['swap']['in']);
        PHPUnit::assertEquals('BTC', $found_available_swaps[0]['swap']['out']);
        PHPUnit::assertEquals(0.001, $found_available_swaps[0]['swap']['rate']);

        PHPUnit::assertEquals($bot_3['uuid'], $found_available_swaps[1]['bot']['id']);
        PHPUnit::assertEquals('BTC', $found_available_swaps[1]['swap']['out']);

        PHPUnit::assertEquals($bot_2['uuid'], $found_available_swaps[2]['bot']['id']);
        PHPUnit::assertEquals('SOUP', $found_available_swaps[2]['swap']['out']);

        PHPUnit::assertEquals($bot_4['uuid'], $found_available_swaps[3]['bot']['id']);
        PHPUnit::assertEquals('SOUP', $found_available_swaps[3]['swap']['out']);


        // filter by in and outToken
        $found_available_swaps = $this->runSearch(['inToken' => 'TOKENLY', 'outToken' => 'BTC', ]);
        PHPUnit::assertCount(2, $found_available_swaps);
        PHPUnit::assertEquals($bot_2['uuid'], $found_available_swaps[0]['bot']['id']);
        PHPUnit::assertEquals('TOKENLY', $found_available_swaps[0]['swap']['in']);
        PHPUnit::assertEquals('BTC', $found_available_swaps[0]['swap']['out']);
        PHPUnit::assertEquals(0.001, $found_available_swaps[0]['swap']['rate']);
        PHPUnit::assertEquals($bot_3['uuid'], $found_available_swaps[1]['bot']['id']);
        PHPUnit::assertEquals('BTC', $found_available_swaps[1]['swap']['out']);


        // sort by cost
        $found_available_swaps = $this->runSearch(['inToken' => 'TOKENLY', 'outToken' => 'BTC', 'sort' => 'cost', ]);
        PHPUnit::assertCount(2, $found_available_swaps);
        PHPUnit::assertEquals($bot_3['uuid'], $found_available_swaps[0]['bot']['id']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_available_swaps[1]['bot']['id']);


    }


    protected function buildBot($swaps=null, $name_or_vars=null) {
        if ($swaps === null) { $swaps = $this->swaps1(); }
        $bot_vars = [
            'swaps' => $swaps,
        ];

        if ($name_or_vars !== null) {
            if (is_array($name_or_vars)) {
                $bot_vars = array_merge($bot_vars, $name_or_vars);
            } else {
                $bot_vars['name'] = $name_or_vars;
            }
        }

        // default to active
        if (!isset($bot_vars['state'])) { $bot_vars['state'] = 'active'; }

        $helper = app('BotHelper');
        return $helper->newSampleBot(null, $bot_vars);

    }

    protected function swaps1() {
        return [
            [
                'in'       => 'LTBCOIN',
                'out'      => 'BTC',
                'rate'     => 0.00000025,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'BTC',
                'out'      => 'LTBCOIN',
                'rate'     => 95000000,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'XCP',
                'out'      => 'LTBCOIN',
                'rate'     => 50000,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'SWARM',
                'out'      => 'LTBCOIN',
                'rate'     => 600,
                'strategy' => 'rate',
                'min'      => 0,
            ],
        ];
    }

    protected function swaps2() {
        return [
            [
                'in'       => 'TOKENLY',
                'out'      => 'BTC',
                'rate'     => 0.001,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'BTC',
                'out'      => 'TOKENLY',
                'rate'     => 60,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'LTBCOIN',
                'out'      => 'TOKENLY',
                'rate'     => 0.00001,
                'strategy' => 'rate',
                'min'      => 0,
            ],
            [
                'in'       => 'EARLY',
                'in_qty'   => 1,
                'out'      => 'TOKENLY',
                'out_qty'  => 1,
                'strategy' => 'fixed',
            ],
            [
                'in'       => 'TOKENLY',
                'out'      => 'SOUP',
                'rate'     => 1,
                'strategy' => 'rate',
                'min'      => 1,
            ],
            [
                'in'       => 'SOUP',
                'out'      => 'LTBCOIN',
                'rate'     => 100,
                'strategy' => 'rate',
                'min'      => 1,
            ],
        ];
    }

    protected function swaps3() {
        return [
            [
                'in'       => 'BTC',
                'out'      => 'TOKENLY',
                'cost'     => 5.00,
                'strategy' => 'fiat',
            ],
            [
                'in'       => 'TOKENLY',
                'out'      => 'BTC',
                'rate'     => 0.0011,
                'strategy' => 'rate',
                'min'      => 1,
            ],
        ];
    }

    protected function swaps4() {
        return [
            [
                'in'       => 'TOKENLY',
                'out'      => 'SOUP',
                'rate'     => 1,
                'strategy' => 'rate',
                'min'      => 1,
            ],
        ];
    }

    protected function runSearch($attributes) {
        $test_helper = app('APITestHelper');
        return $test_helper->callAPIWithoutAuthenticationAndValidateResponse('GET', '/api/v1/public/availableswaps', $attributes, 200);
    }


}
