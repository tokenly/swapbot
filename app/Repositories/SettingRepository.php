<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Setting;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* SettingRepository
*/
class SettingRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Setting';

    public function findByName($name) {
        return call_user_func([$this->model_type, 'where'], 'name', $name)->first();
    }

    public function createOrUpdate($name, $value) {
        $model = $this->findByName($name);

        $attributes = ['name' => $name, 'value' => $value];
        
        if (!$model) {
            $model = $this->create($attributes);
        } else {
            // update
            $this->update($model, $attributes);
        }


        return $model;
    }

}
