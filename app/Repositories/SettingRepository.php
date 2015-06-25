<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
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



    public function update(Model $model, $attributes) {
        $out = parent::update($model, $attributes);
        Event::fire('settings.updated.'.$model['name'], $model);
        return $out;
    }

    public function delete(Model $model) {
        $out = parent::delete($model);
        Event::fire('settings.deleted.'.$model['name'], $model);
        return $out;
    }

    public function create($attributes) {
        $model = parent::create($attributes);
        Event::fire('settings.created.'.$model['name'], $model);
        return $model;
    }

}
