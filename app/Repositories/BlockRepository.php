<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Block;
use \Exception;

/*
* BlockRepository
*/
class BlockRepository
{

    protected $model_type = 'Swapbot\Models\Block';

    public function findByID($id) {
        return call_user_func([$this->model_type, 'find'], $id);
    }

    public function findBestBlockHeight() {
        return call_user_func([$this->model_type, 'max'], 'height');
    }

    public function findBestBlock() {
        return call_user_func([$this->model_type, 'where'], 'height', $this->findBestBlockHeight())->first();
    }

    public function create($attributes) {
        return call_user_func([$this->model_type, 'create'], $attributes);
    }


}
