<?php

namespace Swapbot\Repositories;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\User;
use Tokenly\LaravelApiProvider\Filter\IndexRequestFilter;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
* WhitelistRepository
*/
class WhitelistRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Whitelist';

    public function findByUser(User $user, IndexRequestFilter $filter=null) {
        return $this->findByUserID($user['id'], $filter);
    }

    public function findByUserID($user_id, IndexRequestFilter $filter=null) {
        // build the query
        $query = $this->prototype_model->where('user_id', $user_id);

        // apply the filter
        if ($filter !== null) { $filter->apply($query); }

        return $query->get();
    }


    public function buildFilterDefinition() {
        return [
            'fields' => [
            ],
            'defaults' => ['sort' => 'id'],
        ];
    }

}
