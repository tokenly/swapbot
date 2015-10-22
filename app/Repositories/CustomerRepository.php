<?php

namespace Swapbot\Repositories;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function migrateSwap($old_swap_id, $new_swap_id) {
        foreach ($this->findBySwapId($old_swap_id) as $old_customer) {
            DB::transaction(function() use ($old_customer, $new_swap_id) {
                $create_vars = $old_customer->toArray();

                // delete the old one
                $this->delete($old_customer);
                
                // create a new one, with the new swap id
                $create_vars['swap_id'] = $new_swap_id;
                try {
                    $this->create($create_vars);
                } catch (QueryException $e) {
                    if ($e->errorInfo[0] == 23000) {
                        // duplicate user email found when trying to migrate
                        Log::warning("duplicate email {$create_vars['email']} found when migrating customers to swap {$new_swap_id}.");
                    } else {
                        throw $e;
                    }
                }
            });
        }
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
