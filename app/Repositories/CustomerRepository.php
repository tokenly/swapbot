<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Customer;
use Swapbot\Models\Swap;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Tokenly\TokenGenerator\TokenGenerator;
use \Exception;

/*
* CustomerRepository
*/
class CustomerRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Customer';

    public function findBySwap(Swap $swap) {
        return $this->findBySwapId($swap['id']);
    }

    public function findBySwapId($swap_id) {
        return $this->prototype_model->where('swap_id', $swap_id)->orderBy('id')->get();
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    
    protected function modifyAttributesBeforeCreate($attributes) {
        $token_generator = new TokenGenerator();

        // create a token
        if (!isset($attributes['unsubscribe_token'])) {
            $attributes['unsubscribe_token'] = $token_generator->generateToken(24, 'U');
        }

        return $attributes;
    }

}
