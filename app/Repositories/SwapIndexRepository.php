<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Repositories\BotRepository;
use Tokenly\LaravelApiProvider\Filter\RequestFilter;
use \Exception;

/*
* SwapIndexRepository
* for available swaps
*/
class SwapIndexRepository
{

    const SATOSHI = 1000000;

    protected $table = 'swap_index';


    function __construct(BotRepository $bot_repository) {
        $this->bot_repository = $bot_repository;
    }

    public function addValues(Bot $bot, $rows) {
        $bot_id = $bot['id'];

        $create_rows = [];
        foreach($rows as $row) {
            if (!isset($row['in'])) { throw new Exception("Missing in", 1); }
            if (!isset($row['out'])) { throw new Exception("Missing out", 1); }
            if (!isset($row['cost'])) { throw new Exception("Missing cost", 1); }
            if (!isset($row['swap_offset'])) { throw new Exception("Missing swap_offset", 1); }

            $create_rows[] = [
                'bot_id'      => $bot_id,
                'swap_offset' => $row['swap_offset'],
                'in'          => $row['in'],
                'out'         => $row['out'],
                'cost'        => $row['cost'] * self::SATOSHI,
            ];
        }

        DB::table($this->table)->insert($create_rows);        
    }

    public function clearIndex(Bot $bot) {
        return DB::table($this->table)
            ->where('bot_id', $bot['id'])
            ->delete();
    }

    /**
    * Returns an array of rows like this   
    * {
    *     "details": {
    *         "in": "LTBCOIN",
    *         "out": "BTC",
    *         "cost": 2.5e-7
    *     },
    *     "swap": {
    *         "strategy": "rate",
    *         "in": "LTBCOIN",
    *         "out": "BTC",
    *         "rate": 2.5e-7,
    *         "min": 0
    *     },
    *     "bot": {
    *         "id": "1",
    *         "uuid": "f33183bd-510e-49ea-854f-dc4e195b674e",
    *         "name": "Sample Bot One",
    *         "swaps": ...
    *     }
    * }    
     */
    public function findByOutToken($token) {
        return $this->findByToken($token, 'out');
    }

    public function findByInToken($token) {
        return $this->findByToken($token, 'in');
    }

    public function buildFindAllFilterDefinition() {
        return [
            'fields' => [
                'inToken' => [
                    'field'     => 'in',
                    'sortField' => 'in',
                ],
                'outToken' => [
                    'field'     => 'out',
                    'sortField' => 'out',
                ],
                'cost' => [
                    'sortField' => 'cost',
                ],
            ],

            'defaults' => ['sort' => ['inToken','outToken']],
        ];
    }

    public function findAll(RequestFilter $filter=null) {
        if ($filter === null) {
            return $this->prototype_model->all();
        }

        $query = DB::table($this->table)->newQuery()->from($this->table);

        if ($filter !== null) {
            $filter->filter($query);
            $filter->limit($query);
            $filter->sort($query);
        }

        return $this->buildResponse($query);
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    
    protected function findByToken($token, $direction) {
        $query = DB::table($this->table)
            ->where($direction, $token);
        return $this->buildResponse($query);
    }

    protected function buildResponse(Builder $query) {
        $bots_by_id = [];
        $swap_details_found = [];
        foreach ($query->get() as $row) {
            $bot_id = $row->bot_id;
            if (!isset($bots_by_id[$bot_id])) {
                $bots_by_id[$bot_id] = $this->bot_repository->findById($bot_id);
            }
            $bot = $bots_by_id[$bot_id];

            $swaps = $bot['swaps'];
            $swap = $swaps[$row->swap_offset];
            $swap_details_found[] = [
                'details' => [
                    'in'   => $row->in,
                    'out'  => $row->out,
                    'cost' => ($row->cost / self::SATOSHI),
                ],
                'swap' => $swap,
                'bot'  => $bot,
            ];
        }

        return $swap_details_found;
    }



}
