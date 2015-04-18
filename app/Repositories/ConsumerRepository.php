<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Consumer;
use Swapbot\Models\Swap;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* ConsumerRepository
*/
class ConsumerRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Consumer';

    public function findBySwap(Swap $swap) {
        return $this->findBySwapId($swap['id']);
    }

    public function findBySwapId($swap_id) {
        return $this->prototype_model->where('swap_id', $swap_id)->orderBy('id')->get();
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    

}
