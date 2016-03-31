<?php

use Swapbot\Models\Data\BotState;
use Swapbot\Repositories\SwapIndexRepository;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use \PHPUnit_Framework_Assert as PHPUnit;

class SwapIndexRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testSwapIndexRepository()
    {
        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $bot = $this->buildBot();

        $swaps_found = $index->findByOutToken('BTC');
        PHPUnit::assertCount(1, $swaps_found);
        PHPUnit::assertEquals(0.00000025, $swaps_found[0]['swap']['rate']);
        PHPUnit::assertEquals($bot['uuid'], $swaps_found[0]['bot']['uuid']);
        PHPUnit::assertEquals(4000000, $swaps_found[0]['details']['cost']);

        $swaps_found = $index->findByInToken('XCP');
        PHPUnit::assertCount(1, $swaps_found);
        PHPUnit::assertEquals(50000, $swaps_found[0]['swap']['rate']);
        PHPUnit::assertEquals('LTBCOIN', $swaps_found[0]['swap']['out']);
        PHPUnit::assertEquals($bot['uuid'], $swaps_found[0]['bot']['uuid']);
        PHPUnit::assertEquals('XCP', $swaps_found[0]['details']['in']);
        PHPUnit::assertEquals('LTBCOIN', $swaps_found[0]['details']['out']);
        PHPUnit::assertEquals(0.00002, $swaps_found[0]['details']['cost']);

        $swaps_found = $index->findByOutToken('LTBCOIN');
        PHPUnit::assertCount(3, $swaps_found);
        PHPUnit::assertEquals(95000000, $swaps_found[0]['swap']['rate']);
        PHPUnit::assertEquals('BTC', $swaps_found[0]['swap']['in']);
        PHPUnit::assertEquals('XCP', $swaps_found[1]['swap']['in']);
        PHPUnit::assertEquals('SWARM', $swaps_found[2]['swap']['in']);
        PHPUnit::assertEquals($bot['uuid'], $swaps_found[0]['bot']['uuid']);

    }


    public function testClearSwapIndexRepository()
    {
        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $bot = $this->buildBot();

        $swaps_found = $index->findByOutToken('LTBCOIN');
        PHPUnit::assertCount(3, $swaps_found);

        // clear
        $index->clearIndex($bot);

        $swaps_found = $index->findByOutToken('LTBCOIN');
        PHPUnit::assertCount(0, $swaps_found);
    }

    public function testFindBotsByToken()
    {
        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot();
        $bot_2 = $this->buildBot($this->swaps2(), 'bot two');

        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_bots = $this->runSearch(['inToken' => 'NOTHING',]);
        PHPUnit::assertCount(0, $found_bots);

        $found_bots = $this->runSearch(['inToken' => 'XCP']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);

        $found_bots = $this->runSearch(['inToken' => 'BTC']);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);

        $found_bots = $this->runSearch(['inToken' => 'EARLY']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);

        ////////////////////////////////////////////////////////////////////////
        // OUT

        $found_bots = $this->runSearch(['outToken' => 'NOTHING',]);
        PHPUnit::assertCount(0, $found_bots);

        $found_bots = $this->runSearch(['outToken' => 'SOUP']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);

        $found_bots = $this->runSearch(['outToken' => 'BTC']);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);

        $found_bots = $this->runSearch(['outToken' => 'BTC']);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);

        $found_bots = $this->runSearch(['outToken' => 'LTBCOIN']);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);


        ////////////////////////////////////////////////////////////////////////
        // OUT with name qualifier

        $found_bots = $this->runSearch(['outToken' => 'LTBCOIN', 'name' => 'bot two']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);

    }

    public function testFindBotsByFiatSwap()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot($this->swaps3(), 'bot one');

        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_bots = $this->runSearch(['inToken' => 'BTC']);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);


        // $found_bots = $this->runSearch(['inToken' => 'USD']);
        // PHPUnit::assertCount(1, $found_bots);
        // PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);
    }

    public function testSortSwapsByCost()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_3 = $this->buildBot($this->swaps3(), 'bot three'); // $5.00
        $bot_2 = $this->buildBot($this->swaps2(), 'bot two'); // $3.33

        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_bots = $this->runSearch(['inToken' => 'BTC', 'outToken' => 'TOKENLY', 'sort' => 'cost', ]);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_3['uuid'], $found_bots[1]['uuid']);

    }

    public function testSortMulipleSwaps()
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

        $found_bots = $this->runSearch(['inToken' => 'TOKENLY', 'outToken' => 'SOUP', 'sort' => 'cost,name', ]);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_4['uuid'], $found_bots[1]['uuid']);

        $found_bots = $this->runSearch(['inToken' => 'TOKENLY', 'outToken' => 'SOUP', 'sort' => 'cost,name desc', ]);
        PHPUnit::assertCount(2, $found_bots);
        PHPUnit::assertEquals($bot_4['uuid'], $found_bots[0]['uuid']);
        PHPUnit::assertEquals($bot_2['uuid'], $found_bots[1]['uuid']);

    }


    public function testFilterInactiveBots()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot($this->swaps1(), 'bot one');
        $bot_2 = $this->buildBot($this->swaps3(), ['name' => 'bot two', ]);

        // update the bot state
        app('Swapbot\Repositories\BotRepository')->update($bot_2, ['state' => BotState::BRAND_NEW]);

        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_bots = $this->runSearch(['inToken' => 'BTC', ]);
        PHPUnit::assertCount(1, $found_bots);
        PHPUnit::assertEquals($bot_1['uuid'], $found_bots[0]['uuid']);

    }

    // ------------------------------------------------------------------------

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
                'rate'     => 0.001,
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
        $bot_repo = app('Swapbot\Repositories\BotRepository');
        $test_helper = app('APITestHelper');
        $request = $test_helper->createAPIRequest('GET', '/something', $attributes);
        $filter = IndexRequestFilter::createFromRequest($request, $bot_repo->buildFindAllFilterDefinition());
        return $bot_repo->findAll($filter);
    }


}
