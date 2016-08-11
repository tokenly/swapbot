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

        // check that the index no longer has swaps from the inactive bot
        //   only 4 swaps from swaps1()
        PHPUnit::assertCount(4, $index->findAll());

    }

    public function testLimitAndFilterInactiveBots()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_0 = $this->buildBot($this->swaps2(), 'bot zero');
        $bot_1 = $this->buildBot($this->swaps1(), 'bot one');
        $bot_2 = $this->buildBot($this->swaps3(), ['name' => 'bot two', ]);

        // update the bot state
        app('Swapbot\Repositories\BotRepository')->update($bot_0, ['state' => BotState::BRAND_NEW]);
        app('Swapbot\Repositories\BotRepository')->update($bot_2, ['state' => BotState::BRAND_NEW]);

        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_swaps = $this->runSwapIndexSearch(['limit' => '4', ]);
        PHPUnit::assertCount(4, $found_swaps);

    }


    public function testLimitAndPageBots()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot($this->swaps4(), 'bot 1');
        $bot_2 = $this->buildBot($this->swaps4(), 'bot 2');
        $bot_3 = $this->buildBot($this->swaps4(), 'bot 3');
        $bot_4 = $this->buildBot($this->swaps4(), 'bot 4');
        $bot_5 = $this->buildBot($this->swaps4(), 'bot 5');

        $bot_6 = $this->buildBot($this->swaps4(), 'bot 6');
        $bot_7 = $this->buildBot($this->swaps4(), 'bot 7');
        $bot_8 = $this->buildBot($this->swaps4(), 'bot 8');
        $bot_9 = $this->buildBot($this->swaps4(), 'bot 9');
        $bot_10 = $this->buildBot($this->swaps4(), 'bot 10');

        $bot_11 = $this->buildBot($this->swaps4(), 'bot 11');


        ////////////////////////////////////////////////////////////////////////
        // IN

        $found_swaps = $this->runSwapIndexSearch(['limit' => '5', ]);
        PHPUnit::assertCount(5, $found_swaps);
        PHPUnit::assertEquals($bot_1['id'], $found_swaps[0]['bot']['id']);

        $found_swaps = $this->runSwapIndexSearch(['limit' => '5', 'pg' => 1 ]);
        PHPUnit::assertCount(5, $found_swaps);
        PHPUnit::assertEquals($bot_6['id'], $found_swaps[0]['bot']['id']);

        $found_swaps = $this->runSwapIndexSearch(['limit' => '5', 'pg' => 2 ]);
        PHPUnit::assertCount(1, $found_swaps);
        PHPUnit::assertEquals($bot_11['id'], $found_swaps[0]['bot']['id']);

        $found_swaps = $this->runSwapIndexSearch(['limit' => '5', 'pg' => 3 ]);
        PHPUnit::assertCount(0, $found_swaps);
    }

    public function testFilterWhitelistedBots()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $bot_1 = $this->buildBot($this->swaps1(), 'bot one');
        $bot_2 = $this->buildBot($this->swaps3(), ['name' => 'bot two', ]);

        // find all six swaps
        $found_swaps = $this->runSwapIndexSearch(['whitelisted' => 'false', ]);
        PHPUnit::assertCount(6, $found_swaps);

        // make bot 2 whitelisted
        app('Swapbot\Repositories\BotRepository')->update($bot_2, ['whitelist_uuid' => 'foo']);

        // find only the non-whitelisted swaps
        $found_swaps = $this->runSwapIndexSearch(['whitelisted' => 0, ]);
        PHPUnit::assertCount(4, $found_swaps);

        // find only the whitelisted swaps
        $found_swaps = $this->runSwapIndexSearch(['whitelisted' => true, ]);
        PHPUnit::assertCount(2, $found_swaps);

        // find all
        $found_swaps = $this->runSwapIndexSearch([]);
        PHPUnit::assertCount(6, $found_swaps);

    }


    public function testFilterByUsername()
    {
        app('Tokenly\QuotebotClient\Mock\MockBuilder')->installQuotebotMockClient();

        $index = app('Swapbot\Repositories\SwapIndexRepository');
        $helper = app('BotHelper');
        $test_helper = app('APITestHelper');
        $user_1 = app('UserHelper')->newSampleUser();
        $bot_1 = $this->buildBot($this->swaps1(), 'bot one', $user_1);
        $user_2 = app('UserHelper')->newSampleUser([
            'email'    => 'sample2@tokenly.co',
            'username' => 'sample2',
            'apitoken' => 'TESTAPITOKEN2',
        ]);
        $bot_2 = $this->buildBot($this->swaps3(), ['name' => 'bot two', ], $user_2);

        // find all six swaps
        $found_swaps = $this->runSwapIndexSearch([]);
        PHPUnit::assertCount(6, $found_swaps);

        // find user 1
        $found_swaps = $this->runSwapIndexSearch(['username' => 'leroyjenkins', ]);
        PHPUnit::assertCount(4, $found_swaps);

        // find only the whitelisted swaps
        $found_swaps = $this->runSwapIndexSearch(['username' => 'sample2', ]);
        PHPUnit::assertCount(2, $found_swaps);

        // find only the whitelisted swaps
        $found_swaps = $this->runSwapIndexSearch(['username' => 'sample2,leroyjenkins', ]);
        PHPUnit::assertCount(6, $found_swaps);

    }


    // ------------------------------------------------------------------------

    protected function buildBot($swaps=null, $name_or_vars=null, $user=null) {
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
        return $helper->newSampleBot($user, $bot_vars);

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

    protected function runSwapIndexSearch($attributes) {
        $swap_index = app('Swapbot\Repositories\SwapIndexRepository');
        $test_helper = app('APITestHelper');
        $request = $test_helper->createAPIRequest('GET', '/something', $attributes);
        $filter = IndexRequestFilter::createFromRequest($request, $swap_index->buildFindAllFilterDefinition());
        return $swap_index->findAll($filter);
    }


}
